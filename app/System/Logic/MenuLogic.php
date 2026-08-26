<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemMenu;
use App\Model\SystemPermission;
use Hyperf\Di\Annotation\Inject;

class MenuLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    /**
     * 获取菜单树（后台菜单管理用）.
     *
     * 菜单已退回纯展示层，不再包含按钮类型节点——
     * 按钮权限现在是 system_permission 里的一等公民。
     */
    public function getMenuTree(int $parentId = 0): array
    {
        $menus = SystemMenu::query()
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->buildTreeFromList($menus, $parentId, false);
    }

    /**
     * 获取用户可见的路由菜单（前端用）.
     *
     * 可见性由权限派生，不再独立授权菜单：
     *   - 菜单（type=2）：permission_id 为空则登录可见，否则需持有该权限
     *   - 目录（type=1）：自身无权限要求，但没有可见子节点时自动隐藏
     *
     * 这样「授了菜单没授接口」或反之的不一致在结构上无法出现。
     */
    public function getUserRoutes(int $adminId): array
    {
        $identity = $this->permissionLogic->identity($adminId);

        $menus = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 权限点ID -> 权限码，用于判定菜单可见性
        $codeMap = SystemPermission::query()
            ->whereIn('id', $menus->pluck('permission_id')->filter()->unique()->all())
            ->pluck('code', 'id')
            ->all();

        $visible = $menus->filter(function (SystemMenu $menu) use ($identity, $codeMap): bool {
            // 目录的可见性由子节点决定，先全部保留，后续剪枝
            if ($menu->type === SystemMenu::TYPE_CATALOG) {
                return true;
            }

            // 未绑定权限点：登录可见（如分析页、工作台）
            if (empty($menu->permission_id)) {
                return true;
            }

            $code = $codeMap[$menu->permission_id] ?? null;

            // 绑定了已失效的权限点：按 fail-closed 处理，不可见
            return $code !== null && $identity->can((string) $code);
        });

        $tree = $this->buildTreeFromList($visible, 0, true);

        return $this->pruneEmptyCatalogs($tree, $visible);
    }

    /**
     * 剪掉没有可见子节点的空目录.
     *
     * 目录本身不承载页面，留着只会在侧边栏显示一个点不开的空壳。
     */
    private function pruneEmptyCatalogs(array $tree, $menus): array
    {
        // 收集目录的路由名，用于识别哪些节点是目录
        $catalogNames = [];
        foreach ($menus as $menu) {
            if ($menu->type === SystemMenu::TYPE_CATALOG) {
                $catalogNames[$menu->name] = true;
            }
        }

        $filter = function (array $nodes) use (&$filter, $catalogNames): array {
            $result = [];

            foreach ($nodes as $node) {
                if (! empty($node['children'])) {
                    $node['children'] = $filter($node['children']);
                }

                // 目录且无可见子节点 -> 丢弃
                if (isset($catalogNames[$node['name'] ?? '']) && empty($node['children'])) {
                    continue;
                }

                $result[] = $node;
            }

            return $result;
        };

        return $filter($tree);
    }

    /**
     * 获取用户的按钮权限列表
     *
     * 与接口权限校验共用同一份数据，统一走 PermissionLogic 以复用缓存。
     */
    public function getUserButtonPermissions(int $adminId): array
    {
        return $this->permissionLogic->getUserPermissionCodes($adminId);
    }


    /**
     * 从菜单列表构建树形结构（统一方法）
     *
     * @param \Hyperf\Database\Model\Collection $menus 菜单集合
     * @param int $parentId 父菜单ID
     * @param bool $isRoute 是否为路由格式（true：路由格式，false：完整菜单数据）
     * @return array
     */
    protected function buildTreeFromList($menus, int $parentId = 0, bool $isRoute = false): array
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
        // 不再下发 authority：路由是否可见已在服务端按权限过滤完成，
        // 前端无需再做一次判定，避免两处规则不一致。
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
     */
    public function createMenu(array $data): array
    {
        $data['permission_id'] = $this->normalizePermissionId($data['permission_id'] ?? null);

        // 处理JSON字段
        if (isset($data['query']) && is_string($data['query'])) {
            $data['query'] = json_decode($data['query'], true);
        }

        // 设置默认值
        $data['status'] = $data['status'] ?? SystemMenu::STATUS_ENABLED;
        $data['sort'] = $data['sort'] ?? 0;

        $menu = SystemMenu::query()->create($data);

        $this->permissionLogic->flushAllCache();

        return $menu->toArray();
    }

    /**
     * 更新菜单
     */
    public function updateMenu(int $id, array $data): array
    {
        $menu = SystemMenu::query()->find($id);

        if (!$menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        if (array_key_exists('permission_id', $data)) {
            $data['permission_id'] = $this->normalizePermissionId($data['permission_id']);
        }

        // 处理JSON字段
        if (isset($data['query']) && is_string($data['query'])) {
            $data['query'] = json_decode($data['query'], true);
        }

        $menu->update($data);

        // 刷新获取最新数据
        $menu->refresh();

        $this->permissionLogic->flushAllCache();

        return $menu->toArray();
    }

    /**
     * 归一化菜单绑定的权限点ID.
     *
     * 空值统一落 NULL，表示登录可见；同时校验权限点真实存在，
     * 避免绑定到脏ID导致菜单永久不可见。
     */
    protected function normalizePermissionId(mixed $permissionId): ?int
    {
        if ($permissionId === null || $permissionId === '' || $permissionId === 0 || $permissionId === '0') {
            return null;
        }

        $id = (int) $permissionId;

        if ($id <= 0) {
            return null;
        }

        if (! SystemPermission::query()->whereKey($id)->exists()) {
            throw new BusinessException('绑定的权限点不存在');
        }

        return $id;
    }

    /**
     * 删除菜单
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

        $this->permissionLogic->flushAllCache();
    }

    /**
     * 修改菜单状态
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

        // 停用按钮会影响权限判定，立即失效缓存
        $this->permissionLogic->flushAllCache();
    }
}