<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;

/**
 * 角色权限关联：角色被授予的权限点.
 *
 * 取代原先的 system_role_menu —— 菜单可见性改由权限派生
 * （system_menu.permission_id），不再作为独立的授权对象，
 * 从结构上消除「授了菜单没授接口」这类不一致。
 *
 * @property int $id ID
 * @property int $role_id 角色ID
 * @property int $permission_id 权限点ID
 * @property Carbon $created_at 创建时间
 * @property-read null|SystemRole $role
 * @property-read null|SystemPermission $permission
 */
class SystemRolePermission extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_role_permission';

    /**
     * 该表只记录授权事实，无需 updated_at.
     */
    public bool $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'role_id', 'permission_id', 'created_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'role_id' => 'integer', 'permission_id' => 'integer', 'created_at' => 'datetime'];

    /**
     * 关联角色.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(SystemRole::class, 'role_id', 'id');
    }

    /**
     * 关联权限点.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(SystemPermission::class, 'permission_id', 'id');
    }
}
