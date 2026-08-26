<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;

/**
 * 权限点：授权的唯一原子单位.
 *
 * 数据由代码中的 #[Permission] 注解扫描同步（php bin/hyperf.php permission:sync），
 * 不手工维护，因此不存在「代码要求的权限」与「数据库登记的权限」两份真相。
 *
 * @property int $id ID
 * @property string $code 权限标识，如 system:role:list
 * @property string $name 权限显示名，如 查看列表
 * @property string $module 分组标识，权限码去掉末段，如 system:role
 * @property string $module_name 分组显示名，如 角色管理
 * @property string $http_method 派生数据，仅审计展示
 * @property string $http_path 派生数据，仅审计展示
 * @property string $handler Controller@method，仅审计展示
 * @property int $is_synced 与代码同步状态：1-已同步，0-代码已移除待人工清理
 * @property int $sort 同分组内排序
 * @property int $module_sort 分组排序
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property-read null|Collection|SystemRole[] $roles
 */
class SystemPermission extends Model
{
    /**
     * 已与代码同步.
     */
    public const SYNCED_YES = 1;

    /**
     * 代码中已移除但仍有角色授权，待人工确认后清理.
     *
     * 直接硬删会静默撤销角色授权，标记为未同步可以让管理员看见并决策。
     */
    public const SYNCED_NO = 0;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_permission';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'code', 'name', 'module', 'module_name', 'http_method', 'http_path', 'handler', 'is_synced', 'sort', 'module_sort', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'is_synced' => 'integer', 'sort' => 'integer', 'module_sort' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 关联角色 - 多对多.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SystemRole::class, 'system_role_permission', 'permission_id', 'role_id');
    }
}
