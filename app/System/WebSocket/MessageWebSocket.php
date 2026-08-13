<?php

declare(strict_types=1);

namespace App\System\WebSocket;

use App\Exception\BusinessException;
use App\System\Service\MessageConnectionService;
use App\System\Service\MessageService;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\WebSocketServer\Context;
use Hyperf\WebSocketServer\Sender;
use JsonException;

class MessageWebSocket implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly MessageConnectionService $connectionService,
        private readonly Sender $sender,
    ) {
    }

    public function onOpen($server, $request): void
    {
        $fd = (int) ($request->fd ?? 0);
        $adminId = (int) Context::get('admin_id', 0, $fd);
        if ($adminId <= 0 || $fd <= 0) {
            return;
        }
        $this->connectionService->connect($adminId, $fd);
        $this->push($fd, [
            'type' => 'connected',
            'data' => ['unread_count' => $this->messageService->getUnreadCount($adminId)],
        ]);
    }

    public function onMessage($server, $frame): void
    {
        $adminId = (int) Context::get('admin_id');
        $fd = (int) ($frame->fd ?? 0);
        if ($adminId <= 0 || $fd <= 0) {
            return;
        }
        $this->connectionService->heartbeat($adminId, $fd);

        try {
            $payload = json_decode((string) ($frame->data ?? ''), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new BusinessException('消息格式错误');
            }
            $type = (string) ($payload['type'] ?? '');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            match ($type) {
                'ping' => $this->push($fd, ['type' => 'pong', 'data' => ['time' => time()]]),
                'message.send' => $this->sendMessage($adminId, $fd, $data),
                'message.read' => $this->readConversation($adminId, $data),
                default => throw new BusinessException('不支持的消息类型'),
            };
        } catch (JsonException) {
            $this->pushError($fd, '消息格式错误');
        } catch (BusinessException $exception) {
            $this->pushError($fd, $exception->getMessage(), (string) ($payload['data']['client_id'] ?? ''));
        }
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        $adminId = (int) Context::get('admin_id', 0, $fd);
        if ($adminId > 0) {
            $this->connectionService->disconnect($adminId, $fd);
        }
    }

    private function sendMessage(int $adminId, int $fd, array $data): void
    {
        $clientId = (string) ($data['client_id'] ?? '');
        $message = $this->messageService->send(
            $adminId,
            (int) ($data['recipient_id'] ?? 0),
            (string) ($data['content'] ?? ''),
        );
        $event = ['type' => 'message.new', 'data' => ['message' => $message, 'client_id' => $clientId]];
        $this->connectionService->pushToUser((int) $message['sender_id'], $event);
        $this->connectionService->pushToUser((int) $message['receiver_id'], $event);
        $this->push($fd, ['type' => 'message.ack', 'data' => ['client_id' => $clientId, 'message_id' => $message['id']]]);
    }

    private function readConversation(int $adminId, array $data): void
    {
        $peerId = (int) ($data['peer_id'] ?? 0);
        $result = $this->messageService->markConversationRead($adminId, $peerId);
        if ($result['message_ids'] === []) {
            return;
        }
        $event = [
            'type' => 'message.read',
            'data' => [
                'reader_id' => $adminId,
                'peer_id' => $peerId,
                'message_ids' => $result['message_ids'],
                'read_at' => $result['read_at'],
            ],
        ];
        $this->connectionService->pushToUser($adminId, $event);
        $this->connectionService->pushToUser($peerId, $event);
    }

    private function pushError(int $fd, string $message, string $clientId = ''): void
    {
        $this->push($fd, [
            'type' => 'error',
            'data' => ['message' => $message, 'client_id' => $clientId],
        ]);
    }

    private function push(int $fd, array $payload): void
    {
        $this->sender->push($fd, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
