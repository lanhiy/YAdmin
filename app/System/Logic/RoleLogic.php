<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdminRole;
use App\Model\SystemRole;
use App\Model\SystemRoleMenu;
use Hyperf\DbConnection\Db;

/** 角色维护以及角色与菜单资源关系同步逻辑。 */
class RoleLogic
{
    /**
     * 按名称、编码和状态筛选角色分页列表。
     *
     * @param array{name?: string, code?: string, status?: int|string, page?: int, page_size?: int} $params 查询和分页参数
     * @return array{list: array<int, array<string, mixed>>, total: int, page: int, page_size: int}
     */
    public function getRoleList(array $params = []): array
    {
        $query = SystemRole::query();

        // 空搜索值不生成 LIKE 条件，避免无意义的全表过滤。
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
     * 获取角色选择器使用的全部启用角色。
     *
     * @return array<int, array<string, mixed>>
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
     * 获取角色详情及其已分配的菜单资源 ID。
     *
     * @param int $id 角色主键
     * @return array<string, mixed>
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
     * 创建角色并在同一事务中保存菜单资源关系。
     *
     * @param array<string, mixed> $data 角色字段及 menu_ids
     * @return array<string, mixed>
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
     * 更新角色以及对应的菜单资源关系。
     *
     * @param int $id 角色主键
     * @param array<string, mixed> $data 角色字段及 menu_ids
     * @return array<string, mixed>
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
     * 软删除尚未分配给管理员的角色，并清理菜单关系。
     *
     * @param int $id 角色主键
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
    }

    /**
     * 修改角色启用状态；禁用后该角色不再参与权限快照。
     *
     * @param int $id 角色主键
     * @param int $status 启用或禁用状态值
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
    }

    /**
     * 以提交的菜单 ID 集合完整替换角色原有资源关系。
     *
     * @param int $roleId 角色主键
     * @param array<int, int|string> $menuIds 菜单资源主键集合
     */
    protected function syncRoleMenus(int $roleId, array $menuIds): void
    {
        // 删除旧的关联
        SystemRoleMenu::query()->where('role_id', $roleId)->delete();

        // 添加新的关联
        if ($menuIds !== []) {
            $data = [];
            foreach (array_unique(array_map('intval', $menuIds)) as $menuId) {
                $data[] = [
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            SystemRoleMenu::query()->insert($data);
        }
    }
}
