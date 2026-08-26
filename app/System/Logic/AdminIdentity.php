<?php

declare(strict_types=1);

namespace App\System\Logic;

/**
 * 管理员鉴权身份快照.
 *
 * 鉴权需要的全部事实（账号状态、超管标志、角色编码、权限码）一次查出、
 * 一个缓存键存取。分散成多个键会在部分失效时产生「状态已禁用但权限码仍在」
 * 之类的不一致中间态，这里用单一快照规避。
 */
final class AdminIdentity
{
    /**
     * @param int $adminId 管理员ID
     * @param bool $exists 账号是否存在
     * @param bool $enabled 账号是否启用
     * @param bool $isSuper 是否超级管理员
     * @param string[] $roleCodes 启用状态的角色编码
     * @param string[] $permissionCodes 拥有的权限标识
     */
    public function __construct(
        public readonly int $adminId,
        public readonly bool $exists,
        public readonly bool $enabled,
        public readonly bool $isSuper,
        public readonly array $roleCodes,
        public readonly array $permissionCodes,
    ) {
    }

    /**
     * 从缓存数组还原.
     */
    public static function fromArray(int $adminId, array $data): self
    {
        return new self(
            $adminId,
            (bool) ($data['exists'] ?? false),
            (bool) ($data['enabled'] ?? false),
            (bool) ($data['is_super'] ?? false),
            array_values(array_filter((array) ($data['role_codes'] ?? []), 'is_string')),
            array_values(array_filter((array) ($data['permission_codes'] ?? []), 'is_string')),
        );
    }

    /**
     * 不存在的账号：token 有效但账号已被删除.
     */
    public static function notFound(int $adminId): self
    {
        return new self($adminId, false, false, false, [], []);
    }

    /**
     * 序列化为缓存结构.
     */
    public function toArray(): array
    {
        return [
            'exists' => $this->exists,
            'enabled' => $this->enabled,
            'is_super' => $this->isSuper,
            'role_codes' => $this->roleCodes,
            'permission_codes' => $this->permissionCodes,
        ];
    }

    /**
     * 是否可以通过认证：账号存在且启用.
     */
    public function isActive(): bool
    {
        return $this->exists && $this->enabled;
    }

    /**
     * 是否拥有指定权限.
     *
     * 超管直接放行，不查权限码集合。
     */
    public function can(string $code): bool
    {
        if ($this->isSuper) {
            return true;
        }

        return in_array($code, $this->permissionCodes, true);
    }

    /**
     * 是否拥有任意一个权限.
     *
     * @param string[] $codes
     */
    public function canAny(array $codes): bool
    {
        if ($this->isSuper) {
            return true;
        }

        foreach ($codes as $code) {
            if (in_array($code, $this->permissionCodes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 是否拥有全部权限.
     *
     * @param string[] $codes
     */
    public function canAll(array $codes): bool
    {
        if ($this->isSuper) {
            return true;
        }

        foreach ($codes as $code) {
            if (! in_array($code, $this->permissionCodes, true)) {
                return false;
            }
        }

        return $codes !== [];
    }
}
