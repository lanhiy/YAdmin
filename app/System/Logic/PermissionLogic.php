<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Model\SystemAdmin;
use App\Model\SystemAdminRole;
use App\Model\SystemMenu;
use App\Model\SystemRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Throwable;

/**
 * RBAC 权限查询中心。
 *
 * 权限来源只有一条链：管理员 -> 启用角色 -> 已授权菜单/按钮 -> authority。
 * 前端按钮与后端接口注解使用同一组 authority，避免两套权限配置漂移。
 */
class PermissionLogic
{
    public const SUPER_ADMIN_ID = 1;

    private const IDENTITY_TTL = 1800;
    private const CACHE_KEY_IDENTITY = 'rbac:menu:identity:';

    #[Inject]
    protected Redis $redis;

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
            return $this->queryIdentity($adminId);
        }

        $identity = $this->queryIdentity($adminId);

        try {
            $this->redis->setex(
                $cacheKey,
                self::IDENTITY_TTL,
                json_encode($identity->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        } catch (Throwable) {
            // Redis 不可用时使用当前数据库快照，不影响鉴权。
        }

        return $identity;
    }

    public function isSuperAdmin(int $adminId): bool
    {
        return $this->identity($adminId)->isSuper;
    }

    public function hasPermission(int $adminId, string $code): bool
    {
        return $this->identity($adminId)->can($code);
    }

    /** @return string[] */
    public function getUserPermissionCodes(int $adminId): array
    {
        return $this->identity($adminId)->permissionCodes;
    }

    /** @return string[] */
    public function getUserRoleCodes(int $adminId): array
    {
        return $this->identity($adminId)->roleCodes;
    }

    public function flushAdminCache(int $adminId): void
    {
        try {
            $this->redis->del(self::CACHE_KEY_IDENTITY . $adminId);
        } catch (Throwable) {
            // 缓存最多延迟一个 TTL 失效。
        }
    }

    public function flushRoleCache(int $roleId): void
    {
        $adminIds = SystemAdminRole::query()->where('role_id', $roleId)->pluck('admin_id')->all();
        foreach ($adminIds as $adminId) {
            $this->flushAdminCache((int) $adminId);
        }
    }

    public function flushAllCache(): void
    {
        $adminIds = SystemAdminRole::query()->distinct()->pluck('admin_id')->all();
        foreach ($adminIds as $adminId) {
            $this->flushAdminCache((int) $adminId);
        }
        $this->flushAdminCache(self::SUPER_ADMIN_ID);
    }

    private function queryIdentity(int $adminId): AdminIdentity
    {
        $admin = SystemAdmin::query()->find($adminId, ['id', 'status']);
        if (! $admin instanceof SystemAdmin) {
            return AdminIdentity::notFound($adminId);
        }

        $roles = SystemAdminRole::query()
            ->where('system_admin_role.admin_id', $adminId)
            ->join('system_role', 'system_role.id', '=', 'system_admin_role.role_id')
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->get(['system_role.id', 'system_role.code']);

        $roleIds = $roles->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $roleCodes = $roles->pluck('code')->map(static fn ($code): string => (string) $code)->unique()->values()->all();
        $isSuper = $adminId === self::SUPER_ADMIN_ID;

        return new AdminIdentity(
            $adminId,
            true,
            (int) $admin->status === SystemAdmin::STATUS_ENABLED,
            $isSuper,
            $roleCodes,
            $isSuper ? $this->queryAllPermissionCodes() : $this->queryRolePermissionCodes($roleIds),
        );
    }

    /** @param int[] $roleIds @return string[] */
    private function queryRolePermissionCodes(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $authorities = Db::table('system_role_menu as rm')
            ->join('system_menu as m', 'm.id', '=', 'rm.menu_id')
            ->whereIn('rm.role_id', $roleIds)
            ->where('m.status', SystemMenu::STATUS_ENABLED)
            ->pluck('m.authority')
            ->all();

        return $this->flattenAuthorities($authorities);
    }

    /** @return string[] */
    private function queryAllPermissionCodes(): array
    {
        return $this->flattenAuthorities(
            SystemMenu::query()
                ->where('status', SystemMenu::STATUS_ENABLED)
                ->pluck('authority')
                ->all(),
        );
    }

    /** @param array<int, mixed> $values @return string[] */
    private function flattenAuthorities(array $values): array
    {
        $codes = [];
        foreach ($values as $value) {
            $items = is_array($value) ? $value : json_decode((string) $value, true);
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $code) {
                $code = trim((string) $code);
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }
        }

        return array_keys($codes);
    }
}
