<?php

declare(strict_types=1);

namespace App\System\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

/**
 * 管理员登录会话版本。
 *
 * 同一账号可以同时登录多个端，因此不记录单个 JWT，而是为账号维护一个
 * 会话标识。踢下线时轮换标识，所有旧令牌都会立即失效。
 */
class AdminSessionService
{
    private int $ttl;

    public function __construct(
        private readonly Redis $redis,
        ConfigInterface $config,
    ) {
        $this->ttl = max(
            (int) $config->get('jwt.ttl', 3600),
            (int) $config->get('jwt.refresh_ttl', 3600 * 24 * 14),
        );
    }

    public function getOrCreate(int $adminId): string
    {
        $key = $this->sessionKey($adminId);
        $session = $this->redis->get($key);
        if (is_string($session) && $session !== '') {
            $this->redis->expire($key, $this->ttl);
            return $session;
        }

        $session = bin2hex(random_bytes(24));
        if ($this->redis->set($key, $session, ['NX', 'EX' => $this->ttl])) {
            return $session;
        }

        $existing = $this->redis->get($key);
        return is_string($existing) && $existing !== '' ? $existing : $this->getOrCreate($adminId);
    }

    public function rotate(int $adminId): void
    {
        $this->redis->setex($this->sessionKey($adminId), $this->ttl, bin2hex(random_bytes(24)));
        $this->redis->setex($this->revokedKey($adminId), $this->ttl, (string) time());
    }

    public function isValid(int $adminId, int $issuedAt, string $session): bool
    {
        $current = $this->redis->get($this->sessionKey($adminId));
        if (is_string($current) && $current !== '' && $session !== '') {
            return hash_equals($current, $session);
        }

        // 兼容加入会话版本前签发的令牌：踢下线时间以前签发的令牌失效。
        $revokedAt = (int) $this->redis->get($this->revokedKey($adminId));
        return $revokedAt <= 0 || $issuedAt > $revokedAt;
    }

    private function sessionKey(int $adminId): string
    {
        return 'auth:session:' . $adminId;
    }

    private function revokedKey(int $adminId): string
    {
        return 'auth:revoked:' . $adminId;
    }
}
