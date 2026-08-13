<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Controller\AbstractController;
use App\System\Service\MessageConnectionService;
use App\System\Service\MessageService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly MessageConnectionService $connectionService,
    ) {
    }

    public function ticket(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->messageService->createWebSocketTicket((int) $request->getAttribute('admin_id')));
    }

    public function users(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->messageService->getAvailableUsers((int) $request->getAttribute('admin_id')));
    }

    public function conversations(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->messageService->getConversations((int) $request->getAttribute('admin_id')));
    }

    public function history(int $peerId, RequestInterface $request): ResponseInterface
    {
        $before = $request->input('before');
        return $this->success($this->messageService->getHistory(
            (int) $request->getAttribute('admin_id'),
            $peerId,
            $before === null || $before === '' ? null : (int) $before,
            (int) $request->input('limit', 50),
        ));
    }

    public function read(int $peerId, RequestInterface $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->messageService->markConversationRead($adminId, $peerId);
        if ($result['message_ids'] !== []) {
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
        return $this->success($result);
    }

    public function unread(RequestInterface $request): ResponseInterface
    {
        return $this->success([
            'count' => $this->messageService->getUnreadCount((int) $request->getAttribute('admin_id')),
        ]);
    }
}
