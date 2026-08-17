<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemMenu;
use App\System\Service\PermissionService;
use Hyperf\Di\Annotation\Inject;
use JsonException;

/** 菜单资源维护、动态路由构建及权限策略校验逻辑。 */
class MenuLogic
{
    /** 管理员菜单、按钮和权限标识查询服务。 */
    #[Inject]
    protected PermissionService $permissionService;

    /**
     * 获取菜单树（后台管理用）- 包含按钮类型
     *
     * @param int $parentId 起始父菜单 ID
     * @return array<int, array<string, mixed>>
     */
    public function getMenuTree(int $parentId = 0): array
    {
        // ✅ 获取所有类型的菜单，包括按钮
        $menus = SystemMenu::query()
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->buildTreeFromList($menus, $parentId, false);
    }

    /**
     * 获取管理员可访问的 Vben5 动态路由树，不包含按钮资源。
     *
     * @param int $adminId 管理员主键
     * @return array<int, array<string, mixed>>
     */
    public function getUserRoutes(int $adminId): array
    {
        // ✅ 根据用户权限过滤菜单
        $menuIds = $this->permissionService->snapshot($adminId)['menu_ids'];

        // 获取用户有权限的启用菜单（排除按钮类型）
        $menus = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->whereIn('type', [SystemMenu::TYPE_CATALOG, SystemMenu::TYPE_MENU])
            ->whereIn('id', $menuIds)
            ->orderBy('sort', 'asc')
            ->get();

        return $this->buildTreeFromList($menus, 0, true);
    }

    /**
     * 获取用户的按钮权限列表
     *
     * @param int $adminId 管理员主键
     * @return array<int, string>
     */
    public function getUserButtonPermissions(int $adminId): array
    {
        // ✅ 根据用户角色获取按钮权限
        $snapshot = $this->permissionService->snapshot($adminId);

        // 获取用户有权限的启用按钮
        $buttons = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->where('type', SystemMenu::TYPE_BUTTON)
            ->whereIn('id', $snapshot['assigned_menu_ids'])
            ->get(['id', 'name', 'title', 'authority', 'parent_id'])
            ->toArray();

        // 提取权限标识列表（返回扁平化的权限数组）
        $permissions = [];
        foreach ($buttons as $button) {
            if (!empty($button['authority'])) {
                // authority 是 JSON 数组，需要解析
                $authorities = is_string($button['authority'])
                    ? json_decode($button['authority'], true)
                    : $button['authority'];

                if (is_array($authorities)) {
                    $permissions = array_merge($permissions, $authorities);
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * 从菜单列表构建树形结构（统一方法）
     *
     * @param iterable<SystemMenu> $menus 菜单集合
     * @param int $parentId 父菜单ID
     * @param bool $isRoute 是否为路由格式（true：路由格式，false：完整菜单数据）
     * @return array<int, array<string, mixed>>
     */
    protected function buildTreeFromList(iterable $menus, int $parentId = 0, bool $isRoute = false): array
    {
        $result = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id !== $parentId) {
                continue;
            }

            // 根据类型转换数据格式
            $item = $isRoute ? $this->menuToRoute($menu) : $menu->toArray();

            // 递归获取子节点（从同一个列表中）
            $children = $this->buildTreeFromList($menus, $menu->id, $isRoute);
            if (!empty($children)) {
                $item['children'] = $children;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * 菜单转换为路由格式（符合Vben5规范）
     * 只返回路由必需的字段，不包含数据库原始字段
     *
     * @param SystemMenu $menu 菜单模型
     * @return array<string, mixed>
     */
    protected function menuToRoute(SystemMenu $menu): array
    {
        // 构建路由对象，按 Vben5 标准顺序：meta, name, path, redirect, component, children
        $route = [];

        // 1. Meta 配置（放在最前面）
        $meta = $this->buildRouteMeta($menu);
        if (!empty($meta)) {
            $route['meta'] = $meta;
        }

        // 2. 路由名称
        $route['name'] = $menu->name;

        // 3. 路由路径
        $route['path'] = $menu->path;

        // 4. 重定向（如果有）
        if ($menu->redirect) {
            $route['redirect'] = $menu->redirect;
        }

        // 5. 组件路径
        if ($menu->component) {
            $route['component'] = $menu->component;
        }

        return $route;
    }

    /**
     * 构建路由的 meta 配置
     * 只添加有值且非默认值的字段
     *
     * @param SystemMenu $menu 菜单模型
     * @return array<string, mixed>
     */
    protected function buildRouteMeta(SystemMenu $menu): array
    {
        $meta = [];

        // 标题（必需）
        $meta['title'] = $menu->title;

        // 图标
        if ($menu->icon) {
            $meta['icon'] = $menu->icon;
        }
        if ($menu->active_icon) {
            $meta['activeIcon'] = $menu->active_icon;
        }

        // 显示控制（只在为 true 时添加）
        if ($menu->hide_in_menu) {
            $meta['hideInMenu'] = true;
        }
        if ($menu->hide_in_tab) {
            $meta['hideInTab'] = true;
        }
        if ($menu->hide_in_breadcrumb) {
            $meta['hideInBreadcrumb'] = true;
        }
        if ($menu->hide_children_in_menu) {
            $meta['hideChildrenInMenu'] = true;
        }

        // 缓存和权限
        if ($menu->keep_alive) {
            $meta['keepAlive'] = true;
        }
        if ($menu->authority && !empty($menu->authority)) {
            $meta['authority'] = $menu->authority;
        }
        if ($menu->ignore_access) {
            $meta['ignoreAccess'] = true;
        }
        if ($menu->menu_visible_with_forbidden) {
            $meta['menuVisibleWithForbidden'] = true;
        }

        // 徽标
        if ($menu->badge) {
            $meta['badge'] = $menu->badge;
            if ($menu->badge_type && $menu->badge_type !== 'normal') {
                $meta['badgeType'] = $menu->badge_type;
            }
            if ($menu->badge_variants && $menu->badge_variants !== 'success') {
                $meta['badgeVariants'] = $menu->badge_variants;
            }
        }

        // 标签页配置
        if ($menu->affix_tab) {
            $meta['affixTab'] = true;
            if ($menu->affix_tab_order > 0) {
                $meta['affixTabOrder'] = $menu->affix_tab_order;
            }
        }
        if (!$menu->full_path_key) {
            $meta['fullPathKey'] = false;
        }
        if ($menu->active_path) {
            $meta['activePath'] = $menu->active_path;
        }
        if ($menu->max_num_of_open_tab > 0) {
            $meta['maxNumOfOpenTab'] = $menu->max_num_of_open_tab;
        }

        // 外链和iframe
        if ($menu->link) {
            $meta['link'] = $menu->link;
        }
        if ($menu->iframe_src) {
            $meta['iframeSrc'] = $menu->iframe_src;
        }
        if ($menu->open_in_new_window) {
            $meta['openInNewWindow'] = true;
        }

        // 其他配置
        if ($menu->no_basic_layout) {
            $meta['noBasicLayout'] = true;
        }
        if ($menu->query && !empty($menu->query)) {
            $meta['query'] = $menu->query;
        }

        // 排序
        if ($menu->sort !== 0) {
            $meta['order'] = $menu->sort;
        }

        return $meta;
    }

    /**
     * 根据ID获取菜单
     *
     * @return array<string, mixed>
     */
    public function getMenuById(int $id): array
    {
        $menu = SystemMenu::query()->find($id);

        if (!$menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        return $menu->toArray();
    }

    /**
     * 创建菜单
     *
     * @param array<string, mixed> $data 菜单表单数据
     * @return array<string, mixed>
     */
    public function createMenu(array $data): array
    {
        $data = $this->normalizeJsonFields($data);
        $this->validateRouteFields($data);
        $this->validateApiPolicy($data);

        // 设置默认值
        $data['status'] = $data['status'] ?? SystemMenu::STATUS_ENABLED;
        $data['sort'] = $data['sort'] ?? 0;

        $menu = SystemMenu::query()->create($data);

        return $menu->toArray();
    }

    /**
     * 更新菜单
     *
     * @param int $id 菜单主键
     * @param array<string, mixed> $data 菜单表单数据
     * @return array<string, mixed>
     */
    public function updateMenu(int $id, array $data): array
    {
        $menu = SystemMenu::query()->find($id);

        if (!$menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        $data = $this->normalizeJsonFields($data);
        $this->validateRouteFields($data, $menu);
        $this->validateApiPolicy($data, $menu);

        $menu->update($data);

        // 刷新获取最新数据
        $menu->refresh();

        return $menu->toArray();
    }

    /**
     * 删除没有下级资源的菜单。
     *
     * @param int $id 菜单主键
     */
    public function deleteMenu(int $id): void
    {
        $menu = SystemMenu::query()->find($id);

        if (!$menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        // 检查是否有子菜单或子按钮
        $childCount = SystemMenu::query()
            ->where('parent_id', $id)
            ->count();

        if ($childCount > 0) {
            throw new BusinessException('该菜单存在子菜单或按钮权限，无法删除');
        }

        $menu->delete();
    }

    /**
     * 修改菜单资源状态。
     *
     * @param int $id 菜单主键
     * @param int $status 启用或禁用状态值
     */
    public function changeStatus(int $id, int $status): void
    {
        $menu = SystemMenu::query()->find($id);

        if (!$menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        if (!in_array($status, [SystemMenu::STATUS_DISABLED, SystemMenu::STATUS_ENABLED])) {
            throw new BusinessException('状态值不合法');
        }

        $menu->status = $status;
        $menu->save();
    }

    /**
     * 校验目录和页面菜单具备前端路由路径，按钮资源允许路径为空。
     *
     * @param array<string, mixed> $data 当前提交数据
     * @param null|SystemMenu $menu 更新时的原菜单模型
     */
    private function validateRouteFields(array $data, ?SystemMenu $menu = null): void
    {
        $type = array_key_exists('type', $data)
            ? (int) $data['type']
            : ($menu instanceof SystemMenu ? (int) $menu->type : SystemMenu::TYPE_MENU);
        $path = array_key_exists('path', $data)
            ? trim((string) $data['path'])
            : ($menu instanceof SystemMenu ? trim((string) $menu->path) : '');
        if ($type !== SystemMenu::TYPE_BUTTON && $path === '') {
            throw new BusinessException('目录或菜单必须填写路由路径');
        }
    }

    /**
     * 规范菜单中的 JSON 字段和 API 路由格式。
     *
     * @param array<string, mixed> $data 菜单表单数据
     * @return array<string, mixed>
     */
    private function normalizeJsonFields(array $data): array
    {
        foreach (['authority', 'api_routes', 'query'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            try {
                $data[$field] = json_decode($data[$field], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new BusinessException($field . ' 不是有效的 JSON 数据');
            }
        }

        if (isset($data['api_routes']) && is_array($data['api_routes'])) {
            $routes = [];
            foreach ($data['api_routes'] as $route) {
                if (!is_array($route)) {
                    continue;
                }
                $method = strtoupper(trim((string) ($route['method'] ?? '')));
                $path = '/' . trim((string) ($route['path'] ?? ''), '/');
                if ($method !== '' && $path !== '/') {
                    $routes[] = ['method' => $method, 'path' => $path];
                }
            }
            $data['api_routes'] = $routes;
        }

        return $data;
    }

    /**
     * 验证 API 策略拥有权限标识且不会与其他菜单资源重复。
     *
     * @param array<string, mixed> $data 当前提交数据
     * @param null|SystemMenu $menu 更新时的原菜单模型
     */
    private function validateApiPolicy(array $data, ?SystemMenu $menu = null): void
    {
        $existingRoutes = $menu instanceof SystemMenu ? $menu->api_routes : [];
        $routes = array_key_exists('api_routes', $data) ? $data['api_routes'] : $existingRoutes;
        if (!is_array($routes) || $routes === []) {
            return;
        }

        $existingIgnoreAccess = $menu instanceof SystemMenu ? (bool) $menu->ignore_access : false;
        $existingAuthorities = $menu instanceof SystemMenu ? $menu->authority : [];
        $ignoreAccess = (bool) ($data['ignore_access'] ?? $existingIgnoreAccess);
        $authorities = array_key_exists('authority', $data) ? $data['authority'] : $existingAuthorities;
        if (!$ignoreAccess && (!is_array($authorities) || $authorities === [])) {
            throw new BusinessException('配置 API 路由时必须填写权限标识，或明确启用忽略权限');
        }

        $query = SystemMenu::query()->whereNotNull('api_routes');
        if ($menu instanceof SystemMenu) {
            $query->where('id', '!=', $menu->id);
        }

        foreach ($query->get(['id', 'title', 'api_routes']) as $existingMenu) {
            if (!is_array($existingMenu->api_routes)) {
                continue;
            }
            foreach ($routes as $route) {
                foreach ($existingMenu->api_routes as $existingRoute) {
                    if ($this->sameApiRoute($route, $existingRoute)) {
                        throw new BusinessException(sprintf(
                            'API 路由 %s %s 已绑定到菜单“%s”',
                            strtoupper((string) ($route['method'] ?? '')),
                            (string) ($route['path'] ?? ''),
                            (string) $existingMenu->title,
                        ));
                    }
                }
            }
        }
    }

    /**
     * 判断两条 API 策略是否占用相同的方法和规范化路径。
     *
     * @param mixed $first 第一条 API 策略
     * @param mixed $second 第二条 API 策略
     */
    private function sameApiRoute(mixed $first, mixed $second): bool
    {
        if (!is_array($first) || !is_array($second)) {
            return false;
        }

        $firstMethod = strtoupper((string) ($first['method'] ?? ''));
        $secondMethod = strtoupper((string) ($second['method'] ?? ''));
        $methodsOverlap = $firstMethod === '*' || $secondMethod === '*' || $firstMethod === $secondMethod;
        $firstPath = preg_replace('/\{[^}]+\}/', '{}', '/' . trim((string) ($first['path'] ?? ''), '/'));
        $secondPath = preg_replace('/\{[^}]+\}/', '{}', '/' . trim((string) ($second['path'] ?? ''), '/'));

        return $methodsOverlap && $firstPath === $secondPath;
    }
}
