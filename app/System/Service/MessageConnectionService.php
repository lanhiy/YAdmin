<?php

declare(strict_types=1);

namespace App\System\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Hyperf\WebSocketServer\Sender;
use Throwable;

class MessageConnectionService
{
    private const CONNECTION_TTL = 90;

    private int $ttl;

    public function __construct(
        private readonly Redis $redis,
        private readonly Sender $sender,
        ConfigInterface $config,
    ) {
        $this->ttl = (int) $config->get('message.ttl', 2592000);
    }

    public function connect(int $adminId, int $fd): void
    {
        $key = $this->connectionsKey($adminId);
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, $this->ttl);
        $this->heartbeat($adminId, $fd);
    }

    public function heartbeat(int $adminId, int $fd): void
    {
        $key = $this->connectionsKey($adminId);
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, $this->ttl);
        $this->redis->setex($this->ownerKey($fd), self::CONNECTION_TTL, (string) $adminId);
        $becameOnline = (int) $this->redis->sAdd($this->onlineKey(), $adminId) > 0;
        $this->redis->expire($this->onlineKey(), $this->ttl);
        if ($becameOnline) {
            $this->broadcastPresence($adminId, true);
        }
    }

    public function disconnect(int $adminId, int $fd): void
    {
        $key = $this->connectionsKey($adminId);
        $this->redis->sRem($key, $fd);
        $owner = $this->redis->get($this->ownerKey($fd));
        if ((int) $owner === $adminId) {
            $this->redis->del($this->ownerKey($fd));
        }
        $this->isOnline($adminId);
    }

    public function isOnline(int $adminId): bool
    {
        $key = $this->connectionsKey($adminId);
        $fds = $this->redis->sMembers($key);
        $online = false;
        if (is_array($fds)) {
            foreach ($fds as $fdValue) {
                $fd = (int) $fdValue;
                if ($fd > 0 && (int) $this->redis->get($this->ownerKey($fd)) === $adminId) {
                    $online = true;
                    continue;
                }
                $this->redis->sRem($key, $fdValue);
            }
        }
        if ($online) {
            $this->redis->sAdd($this->onlineKey(), $adminId);
            $this->redis->expire($this->onlineKey(), $this->ttl);
            return true;
        }
        if ((int) $this->redis->sRem($this->onlineKey(), $adminId) > 0) {
            $this->broadcastPresence($adminId, false);
        }
        return false;
    }

    public function getOnlineAdminIds(): array
    {
        $ids = $this->redis->sMembers($this->onlineKey());
        if (! is_array($ids)) {
            return [];
        }
        $online = [];
        foreach ($ids as $id) {
            $adminId = (int) $id;
            if ($adminId > 0 && $this->isOnline($adminId)) {
                $online[] = $adminId;
            }
        }
        return $online;
    }

    public function kick(int $adminId, string $reason = '账号已被管理员踢下线'): void
    {
        $key = $this->connectionsKey($adminId);
        $fds = $this->redis->sMembers($key);
        $encoded = json_encode([
            'type' => 'session.kicked',
            'data' => ['message' => $reason],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (is_array($fds)) {
            foreach ($fds as $fdValue) {
                $fd = (int) $fdValue;
                if ($fd <= 0) {
                    continue;
                }
                try {
                    $this->sender->push($fd, $encoded);
                    $this->sender->disconnect($fd, 4001, $reason);
                } catch (Throwable) {
                    // 连接已断开时仍需清理 Redis 中的连接记录。
                }
                $this->disconnect($adminId, $fd);
            }
        }
        $this->redis->del($key);
        if ((int) $this->redis->sRem($this->onlineKey(), $adminId) > 0) {
            $this->broadcastPresence($adminId, false);
        }
    }

    public function pushToUser(int $adminId, array $payload): void
    {
        $key = $this->connectionsKey($adminId);
        $fds = $this->redis->sMembers($key);
        if (! is_array($fds)) {
            return;
        }
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        foreach ($fds as $fdValue) {
            $fd = (int) $fdValue;
            $owner = (int) $this->redis->get($this->ownerKey($fd));
            if ($fd <= 0 || $owner !== $adminId) {
                $this->redis->sRem($key, $fdValue);
                continue;
            }
            try {
                if (! $this->sender->push($fd, $encoded)) {
                    $this->disconnect($adminId, $fd);
                }
            } catch (Throwable) {
                $this->disconnect($adminId, $fd);
            }
        }
        $this->redis->expire($key, $this->ttl);
        $this->isOnline($adminId);
    }

    private function connectionsKey(int $adminId): string
    {
        return 'message:ws:connections:' . $adminId;
    }

    private function ownerKey(int $fd): string
    {
        return 'message:ws:owner:' . $fd;
    }

    private function onlineKey(): string
    {
        return 'message:ws:online';
    }

    private function broadcastPresence(int $adminId, bool $online): void
    {
        $ids = $this->redis->sMembers($this->onlineKey());
        if (! is_array($ids)) {
            return;
        }
        foreach ($ids as $id) {
            $recipientId = (int) $id;
            if ($recipientId <= 0 || $recipientId === $adminId) {
                continue;
            }
            $this->pushToUser($recipientId, [
                'type' => 'presence.update',
                'data' => ['admin_id' => $adminId, 'online' => $online],
            ]);
        }
    }
}
