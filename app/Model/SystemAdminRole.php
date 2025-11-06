<?php

declare(strict_types=1);

namespace App\Model;



use Carbon\Carbon;

/**
 * @property int $id ID
 * @property int $admin_id 管理员ID
 * @property int $role_id 角色ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class SystemAdminRole extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_admin_role';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'admin_id', 'role_id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'admin_id' => 'integer', 'role_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
