<?php

declare(strict_types=1);

namespace App\System\Service;

use App\Model\SystemAdmin;
use App\Model\SystemMenu;
use App\Model\SystemRole;
use App\Model\SystemRoleMenu;

/**
 * 基于 system_menu 的 RBAC 权限服务。
 *
 * 菜单、按钮权限标识和 API 路由策略均来自数据表，角色保存后无需同步第二份规则。
 */
class PermissionService
{
    /** 数据库中约定的超级管理员角色编码。 */
    private const string SUPER_ROLE_CODE = 'superadmin';

    /** 兼容现有数据中不可移除的初始管理员账号。 */
    private const int INITIAL_ADMIN_ID = 1;

    /**
     * 汇总管理员的启用角色、菜单和权限标识。
     *
     * 超级管理员读取全部启用资源；普通管理员只读取已分配给启用角色的资源。
     *
     * @param int $adminId 管理员主键
     * @return array{is_super: bool, roles: array<int, string>, menu_ids: array<int, int>, assigned_menu_ids: array<int, int>, permissions: array<int, string>}
     */
    public function snapshot(int $adminId): array
    {
        $empty = [
            'is_super' => false,
            'roles' => [],
            'menu_ids' => [],
            'assigned_menu_ids' => [],
            'permissions' => [],
        ];

        $admin = SystemAdmin::query()->select(['id', 'status'])->find($adminId);
        if (!$admin instanceof SystemAdmin || (int) $admin->status !== SystemAdmin::STATUS_ENABLED) {
            return $empty;
        }

        $roles = SystemRole::query()
            ->join('system_admin_role', 'system_admin_role.role_id', '=', 'system_role.id')
            ->where('system_admin_role.admin_id', $adminId)
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->get(['system_role.id', 'system_role.code']);

        $roleIds = $roles->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $roleCodes = $roles->pluck('code')->map(static fn ($code): string => (string) $code)->values()->all();
        $isSuper = $adminId === self::INITIAL_ADMIN_ID || in_array(self::SUPER_ROLE_CODE, $roleCodes, true);

        if ($isSuper) {
            $assignedMenuIds = SystemMenu::query()
                ->where('status', SystemMenu::STATUS_ENABLED)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        } elseif ($roleIds !== []) {
            $assignedMenuIds = SystemRoleMenu::query()
                ->whereIn('role_id', $roleIds)
                ->pluck('menu_id')
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        } else {
            $assignedMenuIds = [];
        }

        $menus = $assignedMenuIds === []
            ? []
            : SystemMenu::query()
                ->whereIn('id', $assignedMenuIds)
                ->where('status', SystemMenu::STATUS_ENABLED)
                ->get(['id', 'authority']);

        $permissions = [];
        foreach ($menus as $menu) {
            foreach ($this->decodeAuthorities($menu->authority) as $permission) {
                $permissions[$permission] = true;
            }
        }

        return [
            'is_super' => $isSuper,
            'roles' => $roleCodes,
            'menu_ids' => $this->withParents($assignedMenuIds),
            'assigned_menu_ids' => array_values(array_unique($assignedMenuIds)),
            'permissions' => array_keys($permissions),
        ];
    }

    /**
     * 判断管理员是否拥有指定权限标识。
     *
     * @param int $adminId 管理员主键
     * @param string $permission 权限标识
     */
    public function can(int $adminId, string $permission): bool
    {
        return $this->canAny($adminId, [$permission]);
    }

    /**
     * 判断管理员是否拥有候选权限中的任意一项。
     *
     * 支持精确标识、全局 `*` 和 `system:*` 形式的分段通配标识。
     *
     * @param int $adminId 管理员主键
     * @param array<int, string> $requiredPermissions 接口接受的权限标识
     */
    public function canAny(int $adminId, array $requiredPermissions): bool
    {
        $snapshot = $this->snapshot($adminId);
        if ($snapshot['is_super']) {
            return true;
        }

        foreach ($requiredPermissions as $required) {
            foreach ($snapshot['permissions'] as $granted) {
                if ($granted === '*' || $granted === $required) {
                    return true;
                }
                if (str_ends_with($granted, ':*') && str_starts_with($required, substr($granted, 0, -1))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 从 system_menu.api_routes 查找当前请求的 API 策略。
     *
     * 未命中策略时返回 found=false，由中间件按默认拒绝处理。
     *
     * @param string $method HTTP 请求方法
     * @param string $path 不含查询参数的请求路径
     * @return array{found: bool, ignore_access: bool, permissions: array<int, string>}
     */
    public function resolve(string $method, string $path): array
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');

        $policies = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->whereNotNull('api_routes')
            ->orderBy('id')
            ->get(['authority', 'api_routes', 'ignore_access']);

        $matchedPolicy = null;
        $matchedScore = PHP_INT_MIN;
        foreach ($policies as $policy) {
            foreach ($this->decodeApiRoutes($policy->api_routes) as $route) {
                if ($route['method'] !== '*' && $route['method'] !== $method) {
                    continue;
                }
                if ($this->matchesRoute($route['path'], $path)) {
                    $score = $this->routeSpecificity($route['method'], $route['path']);
                    if ($score <= $matchedScore) {
                        continue;
                    }
                    $matchedScore = $score;
                    $matchedPolicy = [
                        'found' => true,
                        'ignore_access' => (bool) $policy->ignore_access,
                        'permissions' => $this->decodeAuthorities($policy->authority),
                    ];
                }
            }
        }

        return $matchedPolicy ?? ['found' => false, 'ignore_access' => false, 'permissions' => []];
    }

    /**
     * 匹配数据表中的 API 路径模板。
     *
     * `{id}`、`{id:\d+}` 等占位符匹配单个路径段，`*` 匹配剩余任意字符。
     *
     * @param string $pattern 数据表中的路径模板
     * @param string $path 实际请求路径
     */
    public function matchesRoute(string $pattern, string $path): bool
    {
        if ($pattern === $path) {
            return true;
        }
        if ($pattern === '') {
            return false;
        }

        $expression = '';
        foreach (preg_split('/(\\{[^}]+\\}|\\*)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            if ($part === '*') {
                $expression .= '.*';
            } elseif (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $expression .= '[^/]+';
            } else {
                $expression .= preg_quote($part, '#');
            }
        }
        return (bool) preg_match('#^' . $expression . '$#', $path);
    }

    /**
     * 计算路由策略的匹配优先级，精确方法和精确路径优先于占位符及通配符。
     *
     * @param string $method 策略 HTTP 方法
     * @param string $pattern 策略路径模板
     */
    private function routeSpecificity(string $method, string $pattern): int
    {
        $literalPath = preg_replace('/\{[^}]+\}|\*/', '', $pattern) ?? '';
        $methodScore = $method === '*' ? 0 : 100_000;
        $placeholderScore = str_contains($pattern, '{') ? 0 : 10_000;
        $wildcardScore = str_contains($pattern, '*') ? 0 : 10_000;

        return $methodScore + $placeholderScore + $wildcardScore + strlen($literalPath);
    }

    /**
     * 将模型 JSON 字段规范为非空、去重的权限标识数组。
     *
     * @param mixed $authority 模型返回的 JSON 数组或原始 JSON 字符串
     * @return array<int, string>
     */
    private function decodeAuthorities(mixed $authority): array
    {
        if (is_string($authority)) {
            $authority = json_decode($authority, true);
        }
        if (!is_array($authority)) {
            return [];
        }

        $result = [];
        foreach ($authority as $item) {
            if (is_string($item) && trim($item) !== '') {
                $result[] = trim($item);
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * 解析并规范 API 路由策略。
     *
     * @param mixed $routes 模型返回的 JSON 数组或原始 JSON 字符串
     * @return array<int, array{method: string, path: string}>
     */
    private function decodeApiRoutes(mixed $routes): array
    {
        if (is_string($routes)) {
            $routes = json_decode($routes, true);
        }
        if (!is_array($routes)) {
            return [];
        }

        $result = [];
        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $method = strtoupper(trim((string) ($route['method'] ?? '')));
            $path = '/' . trim((string) ($route['path'] ?? ''), '/');
            if ($method !== '' && $path !== '/') {
                $result[] = ['method' => $method, 'path' => $path];
            }
        }
        return $result;
    }

    /**
     * 补齐已授权菜单的全部父级 ID，确保前端动态路由树结构完整。
     *
     * @param array<int, int> $menuIds
     * @return array<int, int>
     */
    private function withParents(array $menuIds): array
    {
        if ($menuIds === []) {
            return [];
        }

        $allIds = array_values(array_unique($menuIds));
        $menus = SystemMenu::query()->whereIn('id', $allIds)->get(['id', 'parent_id']);
        foreach ($menus as $menu) {
            $parentId = (int) $menu->parent_id;
            while ($parentId > 0 && !in_array($parentId, $allIds, true)) {
                $allIds[] = $parentId;
                $parent = SystemMenu::query()->select(['id', 'parent_id'])->find($parentId);
                $parentId = $parent instanceof SystemMenu ? (int) $parent->parent_id : 0;
            }
        }
        return $allIds;
    }
}
