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
        $this->heartbeat($adminId, $fd);
        $key = $this->connectionsKey($adminId);
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, $this->ttl);
    }

    public function heartbeat(int $adminId, int $fd): void
    {
        $this->redis->setex($this->ownerKey($fd), self::CONNECTION_TTL, (string) $adminId);
    }

    public function disconnect(int $adminId, int $fd): void
    {
        $this->redis->sRem($this->connectionsKey($adminId), $fd);
        $owner = $this->redis->get($this->ownerKey($fd));
        if ((int) $owner === $adminId) {
            $this->redis->del($this->ownerKey($fd));
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
    }

    private function connectionsKey(int $adminId): string
    {
        return 'message:ws:connections:' . $adminId;
    }

    private function ownerKey(int $fd): string
    {
        return 'message:ws:owner:' . $fd;
    }
}
