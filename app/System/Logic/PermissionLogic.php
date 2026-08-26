<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Model\SystemAdminRole;
use App\Model\SystemMenu;
use App\Model\SystemRole;
use App\Model\SystemRoleMenu;
use App\Model\SystemRoutePermission;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

/**
 * 权限查询与校验中心（取代原先的 Casbin 实现）.
 *
 * 权限数据没有独立存储：用户权限 = 其角色勾选的按钮菜单上的 authority。
 * 唯一新增的数据是「路由 -> 权限标识」映射（system_route_permission），
 * 因为请求路径与权限标识的对应关系在原有表结构中不存在。
 */
class PermissionLogic
{
    /**
     * 超级管理员账号ID（拥有全部权限，不做校验）.
     */
    public const SUPER_ADMIN_ID = 1;

    /**
     * 用户权限码缓存时长（秒）.
     */
    private const USER_CACHE_TTL = 1800;

    /**
     * 路由映射缓存时长（秒）.
     */
    private const ROUTE_CACHE_TTL = 3600;

    private const CACHE_KEY_PERMISSIONS = 'user:permissions:';

    private const CACHE_KEY_ROLES = 'user:roles:';

    private const CACHE_KEY_ROUTE_MAP = 'system:route_permission_map';

    #[Inject]
    protected Redis $redis;

    /**
     * 判断是否超级管理员.
     */
    public function isSuperAdmin(int $adminId): bool
    {
        return $adminId === self::SUPER_ADMIN_ID;
    }

    /**
     * 获取用户拥有的权限标识列表.
     *
     * @return string[]
     */
    public function getUserPermissionCodes(int $adminId): array
    {
        return $this->remember(
            self::CACHE_KEY_PERMISSIONS . $adminId,
            self::USER_CACHE_TTL,
            fn (): array => $this->queryUserPermissionCodes($adminId),
        );
    }

    /**
     * 获取用户的角色编码列表.
     *
     * @return string[]
     */
    public function getUserRoleCodes(int $adminId): array
    {
        return $this->remember(
            self::CACHE_KEY_ROLES . $adminId,
            self::USER_CACHE_TTL,
            fn (): array => $this->queryUserRoleCodes($adminId),
        );
    }

    /**
     * 判断用户是否拥有指定权限标识.
     */
    public function hasPermission(int $adminId, string $code): bool
    {
        if ($this->isSuperAdmin($adminId)) {
            return true;
        }

        return in_array($code, $this->getUserPermissionCodes($adminId), true);
    }

    /**
     * 解析请求对应的权限要求.
     *
     * @return null|array{authority: null|string, is_public: bool} null 表示该路由未登记
     */
    public function resolveRoute(string $path, string $method): ?array
    {
        $map = $this->getRouteMap();
        $method = strtoupper($method);

        // 先精确匹配，再用占位符归一化后匹配
        foreach ([$path, $this->normalizePath($path)] as $candidate) {
            $hit = $map[$method . ' ' . $candidate] ?? null;
            if ($hit !== null) {
                return $hit;
            }
        }

        return null;
    }

    /**
     * 清除指定用户的权限缓存.
     */
    public function flushAdminCache(int $adminId): void
    {
        $this->redis->del(
            self::CACHE_KEY_PERMISSIONS . $adminId,
            self::CACHE_KEY_ROLES . $adminId,
        );
    }

    /**
     * 清除某角色下所有用户的权限缓存（角色权限变更后立即生效）.
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
     * 清除所有用户的权限缓存（菜单/按钮权限本身变更时使用）.
     */
    public function flushAllUserCache(): void
    {
        $adminIds = SystemAdminRole::query()->distinct()->pluck('admin_id')->all();

        foreach ($adminIds as $adminId) {
            $this->flushAdminCache((int) $adminId);
        }
    }

    /**
     * 清除路由映射缓存.
     */
    public function flushRouteMapCache(): void
    {
        $this->redis->del(self::CACHE_KEY_ROUTE_MAP);
    }

    /**
     * 读取「METHOD PATH => 权限要求」映射表.
     *
     * @return array<string, array{authority: null|string, is_public: bool}>
     */
    private function getRouteMap(): array
    {
        $cached = $this->redis->get(self::CACHE_KEY_ROUTE_MAP);

        if (is_string($cached) && $cached !== '') {
            $decoded = json_decode($cached, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $map = [];
        foreach (SystemRoutePermission::query()->get(['method', 'path', 'authority', 'is_public']) as $route) {
            $map[strtoupper($route->method) . ' ' . $route->path] = [
                'authority' => $route->authority,
                'is_public' => $route->is_public === 1,
            ];
        }

        $this->redis->setex(self::CACHE_KEY_ROUTE_MAP, self::ROUTE_CACHE_TTL, json_encode($map));

        return $map;
    }

    /**
     * 把请求路径中的动态段替换为占位符，如 /system/role/12 => /system/role/{id}.
     */
    private function normalizePath(string $path): string
    {
        $normalized = preg_replace('#/\d+(?=/|$)#', '/{id}', $path) ?? $path;

        return preg_replace('#(/type)/[^/]+$#', '$1/{type}', $normalized) ?? $normalized;
    }

    /**
     * 带缓存读取字符串数组.
     *
     * @param callable(): string[] $resolver
     * @return string[]
     */
    private function remember(string $cacheKey, int $ttl, callable $resolver): array
    {
        $cached = $this->redis->get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            $decoded = json_decode($cached, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $value = $resolver();
        $this->redis->setex($cacheKey, $ttl, json_encode($value));

        return $value;
    }

    /**
     * 查询用户权限标识：角色勾选的启用按钮菜单上的 authority.
     *
     * @return string[]
     */
    private function queryUserPermissionCodes(int $adminId): array
    {
        if ($this->isSuperAdmin($adminId)) {
            return SystemMenu::query()
                ->where('type', SystemMenu::TYPE_BUTTON)
                ->where('status', SystemMenu::STATUS_ENABLED)
                ->whereNotNull('authority')
                ->where('authority', '<>', '')
                ->pluck('authority')
                ->unique()
                ->values()
                ->all();
        }

        $roleIds = $this->queryEnabledRoleIds($adminId);

        if ($roleIds === []) {
            return [];
        }

        return SystemRoleMenu::query()
            ->whereIn('system_role_menu.role_id', $roleIds)
            ->join('system_menu', 'system_menu.id', '=', 'system_role_menu.menu_id')
            ->where('system_menu.type', SystemMenu::TYPE_BUTTON)
            ->where('system_menu.status', SystemMenu::STATUS_ENABLED)
            ->whereNotNull('system_menu.authority')
            ->where('system_menu.authority', '<>', '')
            ->pluck('system_menu.authority')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 查询用户角色编码.
     *
     * @return string[]
     */
    private function queryUserRoleCodes(int $adminId): array
    {
        return SystemAdminRole::query()
            ->where('system_admin_role.admin_id', $adminId)
            ->join('system_role', 'system_role.id', '=', 'system_admin_role.role_id')
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->pluck('system_role.code')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 查询用户启用状态的角色ID.
     *
     * @return int[]
     */
    private function queryEnabledRoleIds(int $adminId): array
    {
        return SystemAdminRole::query()
            ->where('system_admin_role.admin_id', $adminId)
            ->join('system_role', 'system_role.id', '=', 'system_admin_role.role_id')
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->pluck('system_role.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
}
