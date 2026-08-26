<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemRole;
use App\Model\SystemRoleMenu;
use App\Model\SystemAdminRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class RoleLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    /**
     * 获取角色列表（分页）
     */
    public function getRoleList(array $params = []): array
    {
        $query = SystemRole::query();

        // 搜索条件
        // 搜索条件 - 只在值非空时才添加条件
        if (!empty($params['name']) && trim($params['name']) !== '') {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (!empty($params['code']) && trim($params['code']) !== '') {
            $query->where('code', 'like', '%' . $params['code'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        // 分页
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        // 加载菜单权限
        foreach ($list as &$item) {
            $menuIds = SystemRoleMenu::query()
                ->where('role_id', $item['id'])
                ->pluck('menu_id')
                ->toArray();
            $item['menu_ids'] = $menuIds;
        }

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取所有角色（用于下拉选择）
     */
    public function getAllRoles(): array
    {
        return SystemRole::query()
            ->where('status', SystemRole::STATUS_ENABLED)
            ->orderBy('sort', 'asc')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    /**
     * 根据ID获取角色
     */
    public function getRoleById(int $id): array
    {
        $role = SystemRole::query()
            ->find($id);

        if (!$role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        $data = $role->toArray();

        // 获取菜单权限
        $menuIds = SystemRoleMenu::query()
            ->where('role_id', $id)
            ->pluck('menu_id')
            ->toArray();
        $data['menu_ids'] = $menuIds;

        return $data;
    }

    /**
     * 创建角色
     */
    public function createRole(array $data): array
    {
        // 检查编码是否重复
        $exists = SystemRole::query()
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            throw new BusinessException('角色编码已存在');
        }

        $menuIds = $data['menu_ids'] ?? [];
        unset($data['menu_ids']);

        // 设置默认值
        $data['sort'] = $data['sort'] ?? 0;
        $data['status'] = $data['status'] ?? SystemRole::STATUS_ENABLED;

        return Db::transaction(function () use ($data, $menuIds) {
            $role = SystemRole::query()->create($data);

            // 保存菜单权限
            if (!empty($menuIds)) {
                $this->syncRoleMenus($role->id, $menuIds);
            }

            return $role->toArray();
        });
    }

    /**
     * 更新角色
     */
    public function updateRole(int $id, array $data): array
    {
        $role = SystemRole::query()
            ->find($id);

        if (!$role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        // 检查编码是否重复
        $exists = SystemRole::query()
            ->where('code', $data['code'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            throw new BusinessException('角色编码已存在');
        }

        $menuIds = $data['menu_ids'] ?? [];
        unset($data['menu_ids']);

        return Db::transaction(function () use ($role, $data, $menuIds) {
            $role->update($data);

            // 更新菜单权限
            $this->syncRoleMenus($role->id, $menuIds);

            return $role->toArray();
        });
    }

    /**
     * 删除角色
     */
    public function deleteRole(int $id): void
    {
        $role = SystemRole::query()
            ->find($id);

        if (!$role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        // 检查是否有用户关联
        $adminCount = SystemAdminRole::query()
            ->where('role_id', $id)
            ->count();

        if ($adminCount > 0) {
            throw new BusinessException('该角色已分配给用户，无法删除');
        }

        Db::transaction(function () use ($role) {
            // 软删除
            $role->deleted_at = date('Y-m-d H:i:s');
            $role->save();

            // 删除角色菜单关联
            SystemRoleMenu::query()->where('role_id', $role->id)->delete();
        });

        $this->permissionLogic->flushRoleCache($id);
    }

    /**
     * 修改角色状态
     */
    public function changeStatus(int $id, int $status): void
    {
        $role = SystemRole::query()
            ->find($id);

        if (!$role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        if (!in_array($status, [SystemRole::STATUS_DISABLED, SystemRole::STATUS_ENABLED])) {
            throw new BusinessException('状态值不合法');
        }

        $role->status = $status;
        $role->save();

        // 角色停用/启用会影响其下用户的权限，立即失效缓存
        $this->permissionLogic->flushRoleCache($id);
    }

    /**
     * 同步角色菜单权限
     *
     * 角色的接口权限直接由这里的菜单关联派生（按钮菜单上的 authority），
     * 不再额外维护权限表，因此只需要落 system_role_menu 并清缓存。
     */
    protected function syncRoleMenus(int $roleId, array $menuIds): void
    {
        // 删除旧的关联
        SystemRoleMenu::query()->where('role_id', $roleId)->delete();

        // 添加新的关联
        if (!empty($menuIds)) {
            $data = [];
            foreach ($menuIds as $menuId) {
                $data[] = [
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            SystemRoleMenu::query()->insert($data);
        }

        // 权限变更立即生效
        $this->permissionLogic->flushRoleCache($roleId);
    }
}