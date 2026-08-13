<?php

declare(strict_types=1);

namespace App\System\Service;

use App\Exception\BusinessException;
use App\Model\SystemAdmin;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

class MessageService
{
    private int $ttl;

    private int $maxHistory;

    private int $ticketTtl;

    public function __construct(
        private readonly Redis $redis,
        ConfigInterface $config,
    ) {
        $this->ttl = (int) $config->get('message.ttl', 2592000);
        $this->maxHistory = (int) $config->get('message.max_history', 500);
        $this->ticketTtl = (int) $config->get('message.ws_ticket_ttl', 60);
    }

    public function createWebSocketTicket(int $adminId): array
    {
        $ticket = bin2hex(random_bytes(32));
        $this->redis->setex($this->ticketKey($ticket), $this->ticketTtl, (string) $adminId);

        return [
            'ticket' => $ticket,
            'expires_in' => $this->ticketTtl,
        ];
    }

    public function consumeWebSocketTicket(string $ticket): int
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $ticket)) {
            return 0;
        }

        $key = $this->ticketKey($ticket);
        $adminId = $this->redis->getDel($key);
        if ($adminId === false || $adminId === null) {
            return 0;
        }
        return (int) $adminId;
    }

    public function getAvailableUsers(int $adminId): array
    {
        return (new SystemAdmin())->newQuery()
            ->where('status', SystemAdmin::STATUS_ENABLED)
            ->where('id', '!=', $adminId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'username', 'nickname', 'avatar'])
            ->map(static fn (SystemAdmin $admin): array => self::adminSummary($admin))
            ->values()
            ->toArray();
    }

    public function send(int $senderId, int $receiverId, string $content): array
    {
        $content = trim($content);
        if ($receiverId <= 0 || $receiverId === $senderId) {
            throw new BusinessException('消息接收人无效');
        }
        if ($content === '') {
            throw new BusinessException('消息内容不能为空');
        }
        if (mb_strlen($content) > 2000) {
            throw new BusinessException('消息内容不能超过 2000 个字符');
        }

        $admins = (new SystemAdmin())->newQuery()
            ->whereIn('id', [$senderId, $receiverId])
            ->get(['id', 'username', 'nickname', 'avatar', 'status'])
            ->keyBy('id');
        $sender = $admins->get($senderId);
        $receiver = $admins->get($receiverId);
        if (! $sender instanceof SystemAdmin || $sender->status !== SystemAdmin::STATUS_ENABLED) {
            throw new BusinessException('发送人不存在或已停用');
        }
        if (! $receiver instanceof SystemAdmin || $receiver->status !== SystemAdmin::STATUS_ENABLED) {
            throw new BusinessException('接收人不存在或已停用');
        }

        $sequence = (int) $this->redis->incr('message:sequence');
        $createdAt = date('Y-m-d H:i:s');
        $message = [
            'id' => sprintf('%d-%s', $sequence, bin2hex(random_bytes(8))),
            'sequence' => $sequence,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
            'created_at' => $createdAt,
            'read_at' => null,
            'sender' => self::adminSummary($sender),
            'receiver' => self::adminSummary($receiver),
        ];

        $conversationId = $this->conversationId($senderId, $receiverId);
        $historyKey = $this->historyKey($conversationId);
        $encoded = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->redis->zAdd($historyKey, $sequence, $encoded);
        $this->trimHistory($historyKey, $conversationId);
        $this->touch($historyKey, $this->readKey($conversationId));

        foreach ([$senderId, $receiverId] as $adminId) {
            $indexKey = $this->conversationIndexKey($adminId);
            $this->redis->zAdd($indexKey, $sequence, $conversationId);
            $this->touch($indexKey);
        }

        $conversationUnreadKey = $this->conversationUnreadKey($receiverId, $conversationId);
        $unreadKey = $this->unreadKey($receiverId);
        $this->redis->sAdd($conversationUnreadKey, $message['id']);
        $this->redis->sAdd($unreadKey, $message['id']);
        $this->touch($conversationUnreadKey, $unreadKey);

        return $message;
    }

    public function getConversations(int $adminId, int $limit = 100): array
    {
        $indexKey = $this->conversationIndexKey($adminId);
        $conversationIds = $this->redis->zRevRange($indexKey, 0, max(0, min($limit, 100) - 1));
        if (! is_array($conversationIds)) {
            return [];
        }

        $result = [];
        foreach ($conversationIds as $conversationId) {
            $peerId = $this->peerId((string) $conversationId, $adminId);
            if ($peerId <= 0) {
                $this->redis->zRem($indexKey, $conversationId);
                continue;
            }
            $latest = $this->redis->zRevRange($this->historyKey((string) $conversationId), 0, 0);
            if (! is_array($latest) || ! isset($latest[0])) {
                $this->redis->zRem($indexKey, $conversationId);
                continue;
            }
            $message = $this->decodeMessage((string) $latest[0], (string) $conversationId);
            if ($message === null) {
                continue;
            }
            $peer = $message['sender_id'] === $peerId ? $message['sender'] : $message['receiver'];
            $result[] = [
                'peer' => $peer,
                'last_message' => $message,
                'unread_count' => (int) $this->redis->sCard($this->conversationUnreadKey($adminId, (string) $conversationId)),
            ];
            $this->touch(
                $this->historyKey((string) $conversationId),
                $this->conversationUnreadKey($adminId, (string) $conversationId)
            );
        }
        $this->touch($indexKey);

        return $result;
    }

    public function getHistory(int $adminId, int $peerId, ?int $before = null, int $limit = 50): array
    {
        $this->assertPeerExists($adminId, $peerId);
        $conversationId = $this->conversationId($adminId, $peerId);
        $historyKey = $this->historyKey($conversationId);
        $limit = max(1, min($limit, 100));
        $max = $before === null ? '+inf' : (string) max(0, $before - 1);
        $rows = $this->redis->zRevRangeByScore($historyKey, $max, '-inf', ['limit' => [0, $limit]]);
        $messages = [];
        if (is_array($rows)) {
            foreach (array_reverse($rows) as $row) {
                $message = $this->decodeMessage((string) $row, $conversationId);
                if ($message !== null) {
                    $messages[] = $message;
                }
            }
        }
        $this->touch($historyKey, $this->readKey($conversationId));

        return [
            'list' => $messages,
            'has_more' => count($messages) === $limit,
            'next_cursor' => $messages === [] ? null : $messages[0]['sequence'],
        ];
    }

    public function markConversationRead(int $adminId, int $peerId): array
    {
        $this->assertPeerExists($adminId, $peerId);
        $conversationId = $this->conversationId($adminId, $peerId);
        $conversationUnreadKey = $this->conversationUnreadKey($adminId, $conversationId);
        $messageIds = $this->redis->sPop($conversationUnreadKey, $this->maxHistory);
        if (is_string($messageIds)) {
            $messageIds = [$messageIds];
        }
        if (! is_array($messageIds) || $messageIds === []) {
            return ['message_ids' => [], 'read_at' => null];
        }

        $readAt = date('Y-m-d H:i:s');
        $readKey = $this->readKey($conversationId);
        foreach ($messageIds as $messageId) {
            $this->redis->hSet($readKey, (string) $messageId, $readAt);
            $this->redis->sRem($this->unreadKey($adminId), (string) $messageId);
        }
        $this->touch($readKey, $this->unreadKey($adminId));

        return [
            'message_ids' => array_values($messageIds),
            'read_at' => $readAt,
        ];
    }

    public function getUnreadCount(int $adminId): int
    {
        $key = $this->unreadKey($adminId);
        $count = (int) $this->redis->sCard($key);
        $this->touch($key);
        return $count;
    }

    private function trimHistory(string $historyKey, string $conversationId): void
    {
        $overflow = $this->redis->zCard($historyKey) - $this->maxHistory;
        if ($overflow <= 0) {
            return;
        }
        $removed = $this->redis->zRange($historyKey, 0, $overflow - 1);
        $this->redis->zRemRangeByRank($historyKey, 0, $overflow - 1);
        if (! is_array($removed)) {
            return;
        }
        foreach ($removed as $row) {
            $message = json_decode((string) $row, true);
            if (! is_array($message) || empty($message['id'])) {
                continue;
            }
            $receiverId = (int) ($message['receiver_id'] ?? 0);
            if ($receiverId > 0) {
                $this->redis->sRem($this->conversationUnreadKey($receiverId, $conversationId), (string) $message['id']);
                $this->redis->sRem($this->unreadKey($receiverId), (string) $message['id']);
            }
            $this->redis->hDel($this->readKey($conversationId), (string) $message['id']);
        }
    }

    private function decodeMessage(string $row, string $conversationId): ?array
    {
        $message = json_decode($row, true);
        if (! is_array($message) || empty($message['id'])) {
            return null;
        }
        $readAt = $this->redis->hGet($this->readKey($conversationId), (string) $message['id']);
        $message['read_at'] = $readAt === false ? null : $readAt;
        return $message;
    }

    private function assertPeerExists(int $adminId, int $peerId): void
    {
        if ($peerId <= 0 || $peerId === $adminId || ! (new SystemAdmin())->newQuery()->where('id', $peerId)->exists()) {
            throw new BusinessException('消息接收人不存在');
        }
    }

    private static function adminSummary(SystemAdmin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'username' => (string) $admin->username,
            'nickname' => (string) ($admin->nickname ?: $admin->username),
            'avatar' => (string) ($admin->avatar ?? ''),
        ];
    }

    private function conversationId(int $firstId, int $secondId): string
    {
        return min($firstId, $secondId) . ':' . max($firstId, $secondId);
    }

    private function peerId(string $conversationId, int $adminId): int
    {
        $ids = array_map('intval', explode(':', $conversationId, 2));
        if (count($ids) !== 2 || ! in_array($adminId, $ids, true)) {
            return 0;
        }
        return $ids[0] === $adminId ? $ids[1] : $ids[0];
    }

    private function touch(string ...$keys): void
    {
        foreach ($keys as $key) {
            if ($key !== '') {
                $this->redis->expire($key, $this->ttl);
            }
        }
    }

    private function ticketKey(string $ticket): string
    {
        return 'message:ws:ticket:' . $ticket;
    }

    private function historyKey(string $conversationId): string
    {
        return 'message:history:' . $conversationId;
    }

    private function readKey(string $conversationId): string
    {
        return 'message:read:' . $conversationId;
    }

    private function conversationIndexKey(int $adminId): string
    {
        return 'message:conversations:' . $adminId;
    }

    private function conversationUnreadKey(int $adminId, string $conversationId): string
    {
        return sprintf('message:unread:%d:%s', $adminId, $conversationId);
    }

    private function unreadKey(int $adminId): string
    {
        return 'message:unread:' . $adminId;
    }
}
