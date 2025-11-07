<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemMenu;

class MenuLogic
{
    /**
     * 获取菜单树（后台管理用）
     */
    public function getMenuTree(int $parentId = 0): array
    {
        // 一次性获取所有菜单
        $menus = SystemMenu::query()
            ->orderBy('sort', 'asc')
            ->whereIn('type', [SystemMenu::TYPE_CATALOG, SystemMenu::TYPE_MENU])
            ->get();

        return $this->buildTreeFromList($menus, $parentId, false);
    }

    /**
     * 获取用户的路由菜单（前端用）
     * 返回纯净的路由格式，可直接用于 router.addRoute
     */
    public function getUserRoutes(int $adminId): array
    {
        // TODO: 根据用户权限过滤菜单

        // 一次性获取所有启用的菜单（排除按钮类型）
        $menus = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->whereIn('type', [SystemMenu::TYPE_CATALOG, SystemMenu::TYPE_MENU])
            ->orderBy('sort', 'asc')
            ->get();

        return $this->buildTreeFromList($menus, 0, true);
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
        // 处理JSON字段
        if (isset($data['authority']) && is_string($data['authority'])) {
            $data['authority'] = json_decode($data['authority'], true);
        }
        if (isset($data['query']) && is_string($data['query'])) {
            $data['query'] = json_decode($data['query'], true);
        }

        $menu = SystemMenu::query()->create($data);

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

        // 处理JSON字段
        if (isset($data['authority']) && is_string($data['authority'])) {
            $data['authority'] = json_decode($data['authority'], true);
        }
        if (isset($data['query']) && is_string($data['query'])) {
            $data['query'] = json_decode($data['query'], true);
        }

        $menu->update($data);

        return $menu->toArray();
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

        // 检查是否有子菜单
        $childCount = SystemMenu::query()
            ->where('parent_id', $id)
            ->count();

        if ($childCount > 0) {
            throw new BusinessException('该菜单存在子菜单，无法删除');
        }

        $menu->delete();
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
    }
}