<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdminRole;
use App\Model\SystemMenu;
use App\Model\SystemRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class RoleLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    public function getRoleList(array $params = []): array
    {
        $query = SystemRole::query();
        foreach (['name', 'code'] as $field) {
            $value = trim((string) ($params[$field] ?? ''));
            if ($value !== '') {
                $query->where($field, 'like', '%' . $value . '%');
            }
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($params['page_size'] ?? 20)));
        $total = $query->count();
        $roles = $query->orderBy('sort')->orderBy('id')->forPage($page, $pageSize)->get();

        return [
            'list' => $roles->map(fn (SystemRole $role): array => $this->serialize($role))->all(),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    public function getAllRoles(): array
    {
        return SystemRole::query()
            ->where('status', SystemRole::STATUS_ENABLED)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    public function getRoleById(int $id): array
    {
        return $this->serialize($this->role($id));
    }

    public function createRole(array $data): array
    {
        $this->assertCodeUnique((string) $data['code']);
        $menuIds = $this->normalizeIds($data['menu_ids'] ?? []);

        return Db::transaction(function () use ($data, $menuIds): array {
            $role = SystemRole::query()->create($this->values($data));
            $this->syncMenus((int) $role->id, $menuIds);

            return $this->serialize($role);
        });
    }

    public function updateRole(int $id, array $data): array
    {
        $role = $this->role($id);
        $this->assertCodeUnique((string) $data['code'], $id);
        $menuIds = $this->normalizeIds($data['menu_ids'] ?? []);

        Db::transaction(function () use ($role, $data, $menuIds): void {
            $role->fill($this->values($data))->save();
            $this->syncMenus((int) $role->id, $menuIds);
        });

        $this->permissionLogic->flushRoleCache($id);

        return $this->serialize($role->refresh());
    }

    public function deleteRole(int $id): void
    {
        if ($id === 1) {
            throw new BusinessException('不能删除超级管理员角色');
        }
        $this->role($id);
        if (SystemAdminRole::query()->where('role_id', $id)->exists()) {
            throw new BusinessException('角色仍有关联用户，不能删除');
        }

        Db::transaction(function () use ($id): void {
            Db::table('system_role_menu')->where('role_id', $id)->delete();
            SystemRole::query()->whereKey($id)->delete();
        });
        $this->permissionLogic->flushRoleCache($id);
    }

    public function changeStatus(int $id, int $status): void
    {
        if (! in_array($status, [SystemRole::STATUS_DISABLED, SystemRole::STATUS_ENABLED], true)) {
            throw new BusinessException('状态值不合法');
        }
        if ($id === 1 && $status !== SystemRole::STATUS_ENABLED) {
            throw new BusinessException('不能停用超级管理员角色');
        }

        $role = $this->role($id);
        $role->status = $status;
        $role->save();
        $this->permissionLogic->flushRoleCache($id);
    }

    private function role(int $id): SystemRole
    {
        $role = SystemRole::query()->find($id);
        if (! $role instanceof SystemRole) {
            throw new BusinessException('角色不存在');
        }

        return $role;
    }

    private function assertCodeUnique(string $code, int $exceptId = 0): void
    {
        $query = SystemRole::query()->where('code', trim($code));
        if ($exceptId > 0) {
            $query->where('id', '<>', $exceptId);
        }
        if ($query->exists()) {
            throw new BusinessException('角色编码已存在');
        }
    }

    /** @return array<string, int|string> */
    private function values(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'code' => trim((string) $data['code']),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? SystemRole::STATUS_ENABLED),
        ];
    }

    private function serialize(SystemRole $role): array
    {
        $data = $role->toArray();
        $data['menu_ids'] = Db::table('system_role_menu')
            ->where('role_id', $role->id)
            ->pluck('menu_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return $data;
    }

    /** @param int[] $menuIds */
    private function syncMenus(int $roleId, array $menuIds): void
    {
        $validIds = $menuIds === []
            ? []
            : SystemMenu::query()->whereIn('id', $menuIds)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        if (count($validIds) !== count($menuIds)) {
            throw new BusinessException('所选菜单或权限节点不存在');
        }

        Db::table('system_role_menu')->where('role_id', $roleId)->delete();
        if ($validIds === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        Db::table('system_role_menu')->insert(array_map(
            static fn (int $menuId): array => [
                'role_id' => $roleId,
                'menu_id' => $menuId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $validIds,
        ));
    }

    /** @return int[] */
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
}
