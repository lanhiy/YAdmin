<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdminRole;
use App\Model\SystemMenu;
use App\Model\SystemRole;
use App\Model\SystemRoleMenu;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class MenuLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    /** 后台配置使用，返回目录、菜单和按钮组成的完整树。 */
    public function getMenuTree(int $parentId = 0): array
    {
        $menus = SystemMenu::query()->orderBy('sort')->orderBy('id')->get();

        return $this->buildTree($menus, $parentId, false);
    }

    /** 返回当前用户可见的前端路由。 */
    public function getUserRoutes(int $adminId): array
    {
        $query = SystemMenu::query()
            ->where('status', SystemMenu::STATUS_ENABLED)
            ->whereIn('type', [SystemMenu::TYPE_CATALOG, SystemMenu::TYPE_MENU]);

        if (! $this->permissionLogic->isSuperAdmin($adminId)) {
            $query->whereIn('id', $this->getUserMenuIds($adminId));
        }

        $menus = $query->orderBy('sort')->orderBy('id')->get();

        return $this->buildTree($menus, 0, true);
    }

    /** @return string[] */
    public function getUserButtonPermissions(int $adminId): array
    {
        return $this->permissionLogic->getUserPermissionCodes($adminId);
    }

    /** @return int[] */
    protected function getUserMenuIds(int $adminId): array
    {
        $roleIds = SystemAdminRole::query()
            ->where('system_admin_role.admin_id', $adminId)
            ->join('system_role', 'system_role.id', '=', 'system_admin_role.role_id')
            ->where('system_role.status', SystemRole::STATUS_ENABLED)
            ->pluck('system_role.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        if ($roleIds === []) {
            return [];
        }

        $menuIds = SystemRoleMenu::query()
            ->whereIn('role_id', $roleIds)
            ->pluck('menu_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $this->withParentIds($menuIds);
    }

    /** @param int[] $menuIds @return int[] */
    protected function withParentIds(array $menuIds): array
    {
        if ($menuIds === []) {
            return [];
        }

        $parents = SystemMenu::query()->pluck('parent_id', 'id')->toArray();
        $result = array_fill_keys($menuIds, true);
        foreach ($menuIds as $menuId) {
            $parentId = (int) ($parents[$menuId] ?? 0);
            while ($parentId > 0 && ! isset($result[$parentId])) {
                $result[$parentId] = true;
                $parentId = (int) ($parents[$parentId] ?? 0);
            }
        }

        return array_map('intval', array_keys($result));
    }

    /**
     * @param Collection<int, SystemMenu> $menus
     * @return array<int, array<string, mixed>>
     */
    protected function buildTree(Collection $menus, int $parentId, bool $asRoute): array
    {
        $result = [];
        foreach ($menus as $menu) {
            if ((int) $menu->parent_id !== $parentId) {
                continue;
            }

            $item = $asRoute ? $this->menuToRoute($menu) : $menu->toArray();
            $children = $this->buildTree($menus, (int) $menu->id, $asRoute);
            if ($children !== []) {
                $item['children'] = $children;
            }
            $result[] = $item;
        }

        return $result;
    }

    protected function menuToRoute(SystemMenu $menu): array
    {
        $route = [
            'meta' => $this->buildRouteMeta($menu),
            'name' => $menu->name,
            'path' => $menu->path,
        ];
        if ($menu->redirect !== '') {
            $route['redirect'] = $menu->redirect;
        }
        if ($menu->component !== '') {
            $route['component'] = $menu->component;
        }

        return $route;
    }

    protected function buildRouteMeta(SystemMenu $menu): array
    {
        $meta = ['title' => $menu->title];
        $strings = [
            'icon' => $menu->icon,
            'activeIcon' => $menu->active_icon,
            'badge' => $menu->badge,
            'badgeType' => $menu->badge_type,
            'badgeVariants' => $menu->badge_variants,
            'activePath' => $menu->active_path,
            'link' => $menu->link,
            'iframeSrc' => $menu->iframe_src,
        ];
        foreach ($strings as $key => $value) {
            if ($value !== '') {
                $meta[$key] = $value;
            }
        }

        $flags = [
            'hideInMenu' => $menu->hide_in_menu,
            'hideInTab' => $menu->hide_in_tab,
            'hideInBreadcrumb' => $menu->hide_in_breadcrumb,
            'hideChildrenInMenu' => $menu->hide_children_in_menu,
            'keepAlive' => $menu->keep_alive,
            'ignoreAccess' => $menu->ignore_access,
            'menuVisibleWithForbidden' => $menu->menu_visible_with_forbidden,
            'affixTab' => $menu->affix_tab,
            'fullPathKey' => $menu->full_path_key,
            'openInNewWindow' => $menu->open_in_new_window,
            'noBasicLayout' => $menu->no_basic_layout,
        ];
        foreach ($flags as $key => $value) {
            if ((int) $value === 1) {
                $meta[$key] = true;
            }
        }

        if (is_array($menu->query) && $menu->query !== []) {
            $meta['query'] = $menu->query;
        }
        if ((int) $menu->max_num_of_open_tab !== -1) {
            $meta['maxNumOfOpenTab'] = (int) $menu->max_num_of_open_tab;
        }
        if ((int) $menu->sort !== 0) {
            $meta['order'] = (int) $menu->sort;
        }

        return $meta;
    }

    public function getMenuById(int $id): array
    {
        return $this->menu($id)->toArray();
    }

    public function createMenu(array $data): array
    {
        $values = $this->normalizeValues($data);
        $this->assertParentAllowed((int) $values['parent_id'], (int) $values['type']);
        $menu = SystemMenu::query()->create($values);
        $this->permissionLogic->flushAllCache();

        return $menu->toArray();
    }

    public function updateMenu(int $id, array $data): array
    {
        $menu = $this->menu($id);
        $values = $this->normalizeValues($data);
        $this->assertParentAllowed((int) $values['parent_id'], (int) $values['type'], $id);

        if ((int) $values['type'] === SystemMenu::TYPE_BUTTON
            && SystemMenu::query()->where('parent_id', $id)->exists()) {
            throw new BusinessException('存在子节点的菜单不能改为按钮');
        }

        $menu->fill($values)->save();
        $this->permissionLogic->flushAllCache();

        return $menu->refresh()->toArray();
    }

    public function deleteMenu(int $id): void
    {
        $this->menu($id);
        if (SystemMenu::query()->where('parent_id', $id)->exists()) {
            throw new BusinessException('该节点存在子节点，无法删除');
        }

        Db::transaction(function () use ($id): void {
            Db::table('system_role_menu')->where('menu_id', $id)->delete();
            SystemMenu::query()->whereKey($id)->delete();
        });
        $this->permissionLogic->flushAllCache();
    }

    public function changeStatus(int $id, int $status): void
    {
        if (! in_array($status, [SystemMenu::STATUS_DISABLED, SystemMenu::STATUS_ENABLED], true)) {
            throw new BusinessException('状态值不合法');
        }
        $menu = $this->menu($id);
        $menu->status = $status;
        $menu->save();
        $this->permissionLogic->flushAllCache();
    }

    protected function menu(int $id): SystemMenu
    {
        $menu = SystemMenu::query()->find($id);
        if (! $menu instanceof SystemMenu) {
            throw new BusinessException('菜单不存在');
        }

        return $menu;
    }

    /** @return array<string, mixed> */
    protected function normalizeValues(array $data): array
    {
        $data['authority'] = $this->normalizeAuthorities($data['authority'] ?? []);
        $data['query'] = $this->normalizeJsonObject($data['query'] ?? []);
        $data['parent_id'] = (int) ($data['parent_id'] ?? 0);
        $data['type'] = (int) ($data['type'] ?? SystemMenu::TYPE_MENU);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['status'] = (int) ($data['status'] ?? SystemMenu::STATUS_ENABLED);

        if ($data['type'] === SystemMenu::TYPE_BUTTON) {
            $data['path'] = '';
            $data['component'] = '';
            $data['redirect'] = '';
        }

        return $data;
    }

    /** @return string[] */
    protected function normalizeAuthorities(mixed $authority): array
    {
        if (is_string($authority)) {
            $decoded = json_decode($authority, true);
            $authority = is_array($decoded) ? $decoded : preg_split('/[,\s]+/', $authority);
        }
        if (! is_array($authority)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($code): string => trim((string) $code), $authority),
        )));
    }

    protected function normalizeJsonObject(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new BusinessException('路由参数必须是有效的 JSON 对象');
            }

            return $decoded;
        }

        return is_array($value) ? $value : [];
    }

    protected function assertParentAllowed(int $parentId, int $type, int $currentId = 0): void
    {
        if (! in_array($type, [SystemMenu::TYPE_CATALOG, SystemMenu::TYPE_MENU, SystemMenu::TYPE_BUTTON], true)) {
            throw new BusinessException('菜单类型不合法');
        }
        if ($parentId === 0) {
            if ($type === SystemMenu::TYPE_BUTTON) {
                throw new BusinessException('按钮权限必须隶属于菜单');
            }

            return;
        }
        if ($parentId === $currentId) {
            throw new BusinessException('不能选择自身作为父级');
        }

        $parent = $this->menu($parentId);
        if ((int) $parent->type === SystemMenu::TYPE_BUTTON) {
            throw new BusinessException('按钮权限不能作为父级');
        }
        if ($type === SystemMenu::TYPE_BUTTON && (int) $parent->type !== SystemMenu::TYPE_MENU) {
            throw new BusinessException('按钮权限必须直接隶属于页面菜单');
        }

        $cursor = $parent;
        while ((int) $cursor->parent_id > 0) {
            if ((int) $cursor->parent_id === $currentId) {
                throw new BusinessException('不能选择子节点作为父级');
            }
            $cursor = $this->menu((int) $cursor->parent_id);
        }
    }
}
