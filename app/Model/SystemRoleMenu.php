<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id ID
 * @property int $role_id 角色ID
 * @property int $menu_id 菜单ID
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class SystemRoleMenu extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_role_menu';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'role_id', 'menu_id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'role_id' => 'integer', 'menu_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
