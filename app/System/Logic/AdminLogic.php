<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdmin;
use App\Model\SystemAdminRole;
use App\Model\SystemRole;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class AdminLogic
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    /**
     * 获取用户列表（分页）
     */
    public function getAdminList(array $params = []): array
    {
        $query = SystemAdmin::query();

        // 搜索条件
        if (!empty($params['username'])) {
            $query->where('username', 'like', '%' . $params['username'] . '%');
        }
        if (!empty($params['nickname'])) {
            $query->where('nickname', 'like', '%' . $params['nickname'] . '%');
        }
        if (!empty($params['mobile'])) {
            $query->where('mobile', 'like', '%' . $params['mobile'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }
        if (isset($params['gender']) && $params['gender'] !== '') {
            $query->where('gender', $params['gender']);
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        // 分页
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        // 加载角色信息（包括角色名称）
        foreach ($list as &$item) {
            // 获取角色ID列表
            $roleIds = SystemAdminRole::query()
                ->where('admin_id', $item['id'])
                ->pluck('role_id')
                ->toArray();

            $item['role_ids'] = $roleIds;

            // 获取角色详细信息（id 和 name）
            if (!empty($roleIds)) {
                $roles = SystemRole::query()
                    ->whereIn('id', $roleIds)
                    ->select(['id', 'name'])
                    ->get()
                    ->toArray();

                $item['roles'] = $roles;
            } else {
                $item['roles'] = [];
            }

            // 移除密码字段
            unset($item['password']);
        }

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 根据ID获取用户
     */
    public function getAdminById(int $id): array
    {
        $admin = SystemAdmin::query()->find($id);

        if (!$admin instanceof SystemAdmin) {
            throw new BusinessException('用户不存在');
        }

        $data = $admin->toArray();

        // 获取角色ID列表
        $roleIds = SystemAdminRole::query()
            ->where('admin_id', $id)
            ->pluck('role_id')
            ->toArray();
        $data['role_ids'] = $roleIds;

        // 获取角色详细信息
        if (!empty($roleIds)) {
            $roles = SystemRole::query()
                ->whereIn('id', $roleIds)
                ->select(['id', 'name'])
                ->get()
                ->toArray();

            $data['roles'] = $roles;
        } else {
            $data['roles'] = [];
        }

        // 移除密码字段
        unset($data['password']);

        return $data;
    }

    /**
     * 创建用户
     */
    public function createAdmin(array $data): array
    {
        // 检查用户名是否重复
        $exists = SystemAdmin::query()
            ->where('username', $data['username'])
            ->exists();

        if ($exists) {
            throw new BusinessException('用户名已存在');
        }

        // 检查手机号是否重复
        if (!empty($data['mobile'])) {
            $exists = SystemAdmin::query()
                ->where('mobile', $data['mobile'])
                ->exists();

            if ($exists) {
                throw new BusinessException('手机号已存在');
            }
        }

        // 检查邮箱是否重复
        if (!empty($data['email'])) {
            $exists = SystemAdmin::query()
                ->where('email', $data['email'])
                ->exists();

            if ($exists) {
                throw new BusinessException('邮箱已存在');
            }
        }

        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        // 设置默认值
        $data['gender'] = $data['gender'] ?? SystemAdmin::GENDER_UNKNOWN;
        $data['status'] = $data['status'] ?? SystemAdmin::STATUS_ENABLED;
        $data['sort'] = $data['sort'] ?? 0;

        return Db::transaction(function () use ($data, $roleIds) {
            $admin = SystemAdmin::query()->create($data);

            // 保存角色关联
            if (!empty($roleIds)) {
                $this->syncAdminRoles($admin->id, $roleIds);
            }

            $result = $admin->toArray();
            unset($result['password']);
            return $result;
        });
    }

    /**
     * 更新用户
     */
    public function updateAdmin(int $id, array $data): array
    {
        $admin = SystemAdmin::query()->find($id);

        if (!$admin instanceof SystemAdmin) {
            throw new BusinessException('用户不存在');
        }

//        // 检查用户名是否重复
//        $exists = SystemAdmin::query()
//            ->where('username', $data['username'])
//            ->where('id', '!=', $id)
//            ->exists();

//        if ($exists) {
//            throw new BusinessException('用户名已存在');
//        }

        // 检查手机号是否重复
//        if (!empty($data['mobile'])) {
//            $exists = SystemAdmin::query()
//                ->where('mobile', $data['mobile'])
//                ->where('id', '!=', $id)
//                ->exists();
//
//            if ($exists) {
//                throw new BusinessException('手机号已存在');
//            }
//        }

        // 检查邮箱是否重复
//        if (!empty($data['email'])) {
//            $exists = SystemAdmin::query()
//                ->where('email', $data['email'])
//                ->where('id', '!=', $id)
//                ->exists();
//
//            if ($exists) {
//                throw new BusinessException('邮箱已存在');
//            }
//        }

        $roleIds = $data['role_ids'] ?? [];
        unset($data['username']);
        unset($data['role_ids']);

        // 如果传入了密码则更新密码
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        return Db::transaction(function () use ($admin, $data, $roleIds) {
            $admin->update($data);

            // 更新角色关联
            $this->syncAdminRoles($admin->id, $roleIds);

            $result = $admin->toArray();
            unset($result['password']);
            return $result;
        });
    }

    /**
     * 删除用户
     */
    public function deleteAdmin(int $id): void
    {
        $admin = SystemAdmin::query()->find($id);

        if (!$admin instanceof SystemAdmin) {
            throw new BusinessException('用户不存在');
        }

        Db::transaction(function () use ($admin) {
            // 删除用户
            $admin->delete();

            // 删除用户角色关联
            SystemAdminRole::query()->where('admin_id', $admin->id)->delete();
        });

        $this->permissionLogic->flushAdminCache($id);
    }

    /**
     * 修改用户状态
     */
    public function changeStatus(int $id, int $status): void
    {
        $admin = SystemAdmin::query()->find($id);

        if (!$admin instanceof SystemAdmin) {
            throw new BusinessException('用户不存在');
        }

        if (!in_array($status, [SystemAdmin::STATUS_DISABLED, SystemAdmin::STATUS_ENABLED])) {
            throw new BusinessException('状态值不合法');
        }

        $admin->status = $status;
        $admin->save();

        $this->permissionLogic->flushAdminCache($id);
    }

    /**
     * 同步用户角色
     */
    protected function syncAdminRoles(int $adminId, array $roleIds): void
    {
        // 删除旧的关联
        SystemAdminRole::query()->where('admin_id', $adminId)->delete();

        // 添加新的关联
        if (!empty($roleIds)) {
            $data = [];
            foreach ($roleIds as $roleId) {
                $data[] = [
                    'admin_id' => $adminId,
                    'role_id' => $roleId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            SystemAdminRole::query()->insert($data);
        }

        // 用户角色变更立即生效
        $this->permissionLogic->flushAdminCache($adminId);
    }
}