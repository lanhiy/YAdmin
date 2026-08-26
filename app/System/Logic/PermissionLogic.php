<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Model\SystemAdmin;
use App\Model\SystemAdminRole;
use App\Model\SystemPermission;
use App\Model\SystemRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Throwable;

/**
 * 鉴权权威：所有「谁能做什么」的判定都出自这里.
 *
 * 数据模型：
 *   admin --< admin_role >-- role --< role_permission >-- permission
 *   menu.permission_id -> permission（菜单可见性由权限派生）
 *
 * 超管判定两条独立通路（任一成立即放行全部权限）：
 *   1. admin_id === SUPER_ADMIN_ID（硬编码兜底，保证永不被锁死）
 *   2. 持有任一 is_super=1 且启用的角色
 *
 * 用标志列而非匹配 code='superadmin'：按字符串匹配的话，
 * 在后台把角色名改掉就会静默丢失超管身份。
 */
class PermissionLogic
{
    /**
     * 内置超级管理员账号ID.
     *
     * 硬编码兜底：即使角色配置被改坏，该账号仍可登录并修复权限，
     * 避免整个系统被锁死。
     */
    public const SUPER_ADMIN_ID = 1;

    /**
     * 身份快照缓存时长（秒）.
     */
    private const IDENTITY_TTL = 1800;

    /**
     * 身份快照缓存键前缀.
     */
    private const CACHE_KEY_IDENTITY = 'rbac:identity:';

    #[Inject]
    protected Redis $redis;

    /**
     * 读取管理员鉴权身份快照（带缓存）.
     */
    public function identity(int $adminId): AdminIdentity
    {
        $cacheKey = self::CACHE_KEY_IDENTITY . $adminId;

        try {
            $cached = $this->redis->get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                $decoded = json_decode($cached, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return AdminIdentity::fromArray($adminId, $decoded);
                }
            }
        } catch (Throwable) {
            // 缓存不可用时退化为直接查库，不影响鉴权可用性
            return $this->queryIdentity($adminId);
        }

        $identity = $this->queryIdentity($adminId);

        try {
            $this->redis->setex($cacheKey, self::IDENTITY_TTL, json_encode($identity->toArray()));
        } catch (Throwable) {
            // 写缓存失败不影响本次鉴权结果
        }

        return $identity;
    }

    /**
     * 判断是否超级管理员.
     */
    public function isSuperAdmin(int $adminId): bool
    {
        return $this->identity($adminId)->isSuper;
    }

    /**
     * 判断用户是否拥有指定权限.
     */
    public function hasPermission(int $adminId, string $code): bool
    {
        return $this->identity($adminId)->can($code);
    }

    /**
     * 获取用户拥有的权限标识列表（前端按钮级权限用）.
     *
     * @return string[]
     */
    public function getUserPermissionCodes(int $adminId): array
    {
        return $this->identity($adminId)->permissionCodes;
    }

    /**
     * 获取用户的角色编码列表.
     *
     * @return string[]
     */
    public function getUserRoleCodes(int $adminId): array
    {
        return $this->identity($adminId)->roleCodes;
    }

    /**
     * 清除指定用户的身份缓存.
     */
    public function flushAdminCache(int $adminId): void
    {
        try {
            $this->redis->del(self::CACHE_KEY_IDENTITY . $adminId);
        } catch (Throwable) {
            // 缓存清理失败最多延迟 IDENTITY_TTL 生效，不阻断业务
        }
    }

    /**
     * 清除某角色下所有用户的身份缓存（角色授权变更后立即生效）.
     */
    public function flushRoleCache(int $roleId): void
    {
        $adminIds = SystemAdminRole::query()
            ->where('role_id', $roleId)
            ->pluck('admin_id')
            ->all();

        foreach ($adminIds as $adminId) {
            $this->flushAdminCache((int) $adminId);
        }
    }

    /**
     * 清除全部用户的身份缓存（权限点本身变更时使用）.
     */
    public function flushAllCache(): void
    {
        $adminIds = SystemAdminRole::query()->distinct()->pluck('admin_id')->all();

        foreach ($adminIds as $adminId) {
            $this->flushAdminCache((int) $adminId);
        }

        // 内置超管可能没有任何角色关联，需要单独清理
        $this->flushAdminCache(self::SUPER_ADMIN_ID);
    }

    /**
     * 查询管理员身份：账号状态、超管标志、角色、权限一次取全.
     */
    private function queryIdentity(int $adminId): AdminIdentity
    {
        $admin = SystemAdmin::query()->find($adminId, ['id', 'status']);

        if (! $admin instanceof SystemAdmin) {
            return AdminIdentity::notFound($adminId);
        }

        $enabled = $admin->status === SystemAdmin::STATUS_ENABLED;

        // 启用状态的角色：编码 + 超管标志
        $roles = SystemAdminRole::query()
            ->where('system_admin_role.admin_id', $adminId)
            ->join('system_role', 'system_role.id', '=', 'system_admin_role.role_id')
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->get(['system_role.id', 'system_role.code', 'system_role.is_super']);

        $roleCodes = [];
        $roleIds = [];
        $isSuperByRole = false;

        foreach ($roles as $role) {
            $roleCodes[] = (string) $role->code;
            $roleIds[] = (int) $role->id;

            if ((int) $role->is_super === SystemRole::IS_SUPER_YES) {
                $isSuperByRole = true;
            }
        }

        // 两条独立通路：内置超管ID 或 持有超管角色
        $isSuper = $adminId === self::SUPER_ADMIN_ID || $isSuperByRole;

        return new AdminIdentity(
            $adminId,
            true,
            $enabled,
            $isSuper,
            array_values(array_unique($roleCodes)),
            $isSuper ? $this->queryAllPermissionCodes() : $this->queryRolePermissionCodes($roleIds),
        );
    }

    /**
     * 查询角色被授予的权限标识.
     *
     * @param int[] $roleIds
     * @return string[]
     */
    private function queryRolePermissionCodes(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return Db::table('system_role_permission')
            ->whereIn('system_role_permission.role_id', $roleIds)
            ->join('system_permission', 'system_permission.id', '=', 'system_role_permission.permission_id')
            ->distinct()
            ->pluck('system_permission.code')
            ->map(static fn ($code): string => (string) $code)
            ->all();
    }

    /**
     * 超管的权限码集合：全部权限点.
     *
     * 超管的鉴权判定本身不依赖这个列表（can() 直接放行），
     * 查出来只为让前端按钮级权限（access-codes）拿到完整集合。
     *
     * @return string[]
     */
    private function queryAllPermissionCodes(): array
    {
        return SystemPermission::query()
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->all();
    }
}
