<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdminRole;
use App\Model\SystemMenu;
use App\Model\SystemPermission;
use App\Model\SystemRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class RoleLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    /**
     * 获取角色列表（分页）.
     */
    public function getRoleList(array $params = []): array
    {
        $query = SystemRole::query();

        if (! empty($params['name']) && trim($params['name']) !== '') {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }
        if (! empty($params['code']) && trim($params['code']) !== '') {
            $query->where('code', 'like', '%' . $params['code'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        // 批量取授权，避免 N+1
        $grants = $this->loadRoleGrants(array_column($list, 'id'));

        foreach ($list as &$item) {
            $item = $this->appendGrants($item, $grants[$item['id']] ?? []);
        }

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取所有启用角色（下拉选择用）.
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
     * 根据ID获取角色（含授权明细）.
     */
    public function getRoleById(int $id): array
    {
        $role = SystemRole::query()->find($id);

        if (! $role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        $grants = $this->loadRoleGrants([$id]);

        return $this->appendGrants($role->toArray(), $grants[$id] ?? []);
    }

    /**
     * 创建角色.
     */
    public function createRole(array $data): array
    {
        $exists = SystemRole::query()->where('code', $data['code'])->exists();

        if ($exists) {
            throw new BusinessException('角色编码已存在');
        }

        $permissionIds = $this->resolvePermissionIds($data);
        unset($data['permission_ids'], $data['menu_ids'], $data['is_super']);

        $data['sort'] = $data['sort'] ?? 0;
        $data['status'] = $data['status'] ?? SystemRole::STATUS_ENABLED;

        return Db::transaction(function () use ($data, $permissionIds) {
            $role = SystemRole::query()->create($data);

            $this->syncRolePermissions((int) $role->id, $permissionIds);

            return $this->appendGrants(
                $role->toArray(),
                $this->loadRoleGrants([(int) $role->id])[(int) $role->id] ?? [],
            );
        });
    }

    /**
     * 更新角色.
     */
    public function updateRole(int $id, array $data): array
    {
        $role = SystemRole::query()->find($id);

        if (! $role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        $exists = SystemRole::query()
            ->where('code', $data['code'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            throw new BusinessException('角色编码已存在');
        }

        // 超管角色的授权面无意义（本身即全权），禁止改动以免误导
        if ($role->isSuper() && (isset($data['permission_ids']) || isset($data['menu_ids']))) {
            throw new BusinessException('超级管理员角色拥有全部权限，无需也不可单独授权');
        }

        $permissionIds = $this->resolvePermissionIds($data);
        $hasGrantInput = array_key_exists('permission_ids', $data) || array_key_exists('menu_ids', $data);

        // is_super 不允许通过接口变更，避免自行提权
        unset($data['permission_ids'], $data['menu_ids'], $data['is_super']);

        return Db::transaction(function () use ($role, $data, $permissionIds, $hasGrantInput) {
            $role->update($data);

            // 未提交授权字段时不动既有授权，避免编辑基础信息意外清空权限
            if ($hasGrantInput) {
                $this->syncRolePermissions((int) $role->id, $permissionIds);
            }

            $this->permissionLogic->flushRoleCache((int) $role->id);

            return $this->appendGrants(
                $role->toArray(),
                $this->loadRoleGrants([(int) $role->id])[(int) $role->id] ?? [],
            );
        });
    }

    /**
     * 删除角色.
     */
    public function deleteRole(int $id): void
    {
        $role = SystemRole::query()->find($id);

        if (! $role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        if ($role->isSuper()) {
            throw new BusinessException('超级管理员角色不可删除');
        }

        $adminCount = SystemAdminRole::query()->where('role_id', $id)->count();

        if ($adminCount > 0) {
            throw new BusinessException('该角色已分配给用户，无法删除');
        }

        Db::transaction(function () use ($role): void {
            Db::table('system_role_permission')->where('role_id', $role->id)->delete();
            $role->delete();
        });

        $this->permissionLogic->flushRoleCache($id);
    }

    /**
     * 修改角色状态.
     */
    public function changeStatus(int $id, int $status): void
    {
        $role = SystemRole::query()->find($id);

        if (! $role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        if (! in_array($status, [SystemRole::STATUS_DISABLED, SystemRole::STATUS_ENABLED], true)) {
            throw new BusinessException('状态值不合法');
        }

        if ($role->isSuper() && $status === SystemRole::STATUS_DISABLED) {
            throw new BusinessException('超级管理员角色不可禁用');
        }

        $role->status = $status;
        $role->save();

        $this->permissionLogic->flushRoleCache($id);
    }

    /**
     * 解析前端提交的授权.
     *
     * 主契约是 permission_ids；menu_ids 是过渡兼容：前端角色表单尚未改造，
     * 仍在提交菜单ID，这里映射为对应的权限点ID。前端切换后可移除。
     *
     * @return int[]
     */
    private function resolvePermissionIds(array $data): array
    {
        if (array_key_exists('permission_ids', $data)) {
            return $this->normalizeIds($data['permission_ids']);
        }

        if (! array_key_exists('menu_ids', $data)) {
            return [];
        }

        $menuIds = $this->normalizeIds($data['menu_ids']);

        if ($menuIds === []) {
            return [];
        }

        // 菜单绑定的权限点即该页面的访问权限
        return SystemMenu::query()
            ->whereIn('id', $menuIds)
            ->whereNotNull('permission_id')
            ->pluck('permission_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return int[]
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * 批量读取角色授权.
     *
     * @param int[] $roleIds
     * @return array<int, array{permission_ids: int[], menu_ids: int[]}>
     */
    private function loadRoleGrants(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $rows = Db::table('system_role_permission')
            ->whereIn('role_id', $roleIds)
            ->get(['role_id', 'permission_id']);

        $result = [];
        $allPermissionIds = [];

        foreach ($rows as $row) {
            $result[(int) $row->role_id]['permission_ids'][] = (int) $row->permission_id;
            $allPermissionIds[] = (int) $row->permission_id;
        }

        // 反向推导菜单ID，供尚未改造的前端表单回显勾选状态
        $menuMap = [];
        if ($allPermissionIds !== []) {
            foreach (SystemMenu::query()
                ->whereIn('permission_id', array_unique($allPermissionIds))
                ->get(['id', 'permission_id']) as $menu) {
                $menuMap[(int) $menu->permission_id][] = (int) $menu->id;
            }
        }

        foreach ($result as $roleId => $grant) {
            $menuIds = [];
            foreach ($grant['permission_ids'] as $permissionId) {
                foreach ($menuMap[$permissionId] ?? [] as $menuId) {
                    $menuIds[] = $menuId;
                }
            }
            $result[$roleId]['menu_ids'] = array_values(array_unique($menuIds));
        }

        return $result;
    }

    /**
     * 把授权明细附加到角色数据上.
     */
    private function appendGrants(array $role, array $grant): array
    {
        $isSuper = (int) ($role['is_super'] ?? 0) === SystemRole::IS_SUPER_YES;

        // 超管角色回显全部权限，避免前端表单显示为「未授权」
        if ($isSuper) {
            $role['permission_ids'] = SystemPermission::query()
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $role['menu_ids'] = SystemMenu::query()
                ->whereNotNull('permission_id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            return $role;
        }

        $role['permission_ids'] = $grant['permission_ids'] ?? [];
        $role['menu_ids'] = $grant['menu_ids'] ?? [];

        return $role;
    }

    /**
     * 同步角色授权.
     *
     * @param int[] $permissionIds
     */
    private function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        Db::table('system_role_permission')->where('role_id', $roleId)->delete();

        if ($permissionIds !== []) {
            // 过滤不存在的权限点，防止脏ID写入
            $validIds = SystemPermission::query()
                ->whereIn('id', $permissionIds)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $now = date('Y-m-d H:i:s');
            $rows = array_map(
                static fn (int $permissionId): array => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                ],
                $validIds,
            );

            if ($rows !== []) {
                Db::table('system_role_permission')->insert($rows);
            }
        }

        $this->permissionLogic->flushRoleCache($roleId);
    }
}
