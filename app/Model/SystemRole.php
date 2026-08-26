<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;
/**
 * @property-read null|Collection|SystemPermission[] $permissions
 * @property int $id ID
 * @property string $name 角色名称
 * @property string $code 角色编码
 * @property int $is_super 是否超级管理员角色：0-否，1-是
 * @property string $description 角色描述
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property-read null|Collection|SystemAdmin[] $admins
 */
class SystemRole extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_role';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'code', 'description', 'sort', 'status', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'is_super' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 关联管理员 - 多对多
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(SystemAdmin::class,'system_admin_role','role_id','admin_id')->withTimestamps();
    }

    /**
     * 关联权限点 - 多对多.
     *
     * 角色的授权面只有这一处：菜单可见性由权限派生，不再单独授权菜单。
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(SystemPermission::class, 'system_role_permission', 'role_id', 'permission_id');
    }

    /**
     * 是否超级管理员角色.
     */
    public function isSuper(): bool
    {
        return $this->is_super === self::IS_SUPER_YES;
    }

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * 非超级管理员角色.
     */
    public const IS_SUPER_NO = 0;

    /**
     * 超级管理员角色：拥有全部权限，不做逐项校验.
     *
     * 该字段不在 $fillable 中，无法通过角色新增/编辑接口赋值，
     * 只能由数据库运维显式变更，避免普通管理员自行提权。
     */
    public const IS_SUPER_YES = 1;
}
