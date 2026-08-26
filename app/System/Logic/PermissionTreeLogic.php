<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Model\SystemPermission;

/**
 * 权限树：角色授权界面的数据源.
 *
 * 按 module 分组，组为父节点、权限点为叶子节点，
 * 供前端渲染成可勾选的树形结构。
 */
class PermissionTreeLogic
{
    /**
     * 获取权限树.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTree(): array
    {
        $permissions = SystemPermission::query()
            ->orderBy('module_sort')
            ->orderBy('module')
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'module', 'module_name', 'http_method', 'http_path', 'is_synced']);

        $groups = [];

        foreach ($permissions as $permission) {
            $module = (string) $permission->module;

            if (! isset($groups[$module])) {
                $groups[$module] = [
                    // 分组节点用负数 key，避免与权限点ID冲突导致勾选串台
                    'id' => -(count($groups) + 1),
                    'code' => $module,
                    'title' => $permission->module_name !== '' ? $permission->module_name : $module,
                    'is_group' => true,
                    'children' => [],
                ];
            }

            $groups[$module]['children'][] = [
                'id' => (int) $permission->id,
                'code' => (string) $permission->code,
                'title' => (string) $permission->name,
                'is_group' => false,
                'http_method' => (string) $permission->http_method,
                'http_path' => (string) $permission->http_path,
                // 代码中已移除但仍有授权的权限点，前端可标记提示
                'is_synced' => (int) $permission->is_synced === SystemPermission::SYNCED_YES,
            ];
        }

        return array_values($groups);
    }

    /**
     * 获取扁平权限列表（用于权限点管理页）.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getList(array $params = []): array
    {
        $query = SystemPermission::query();

        if (! empty($params['keyword'])) {
            $keyword = trim((string) $params['keyword']);
            $query->where(function ($q) use ($keyword): void {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        if (! empty($params['module'])) {
            $query->where('module', $params['module']);
        }

        if (isset($params['is_synced']) && $params['is_synced'] !== '') {
            $query->where('is_synced', (int) $params['is_synced']);
        }

        return $query
            ->orderBy('module_sort')
            ->orderBy('module')
            ->orderBy('sort')
            ->get()
            ->toArray();
    }
}
