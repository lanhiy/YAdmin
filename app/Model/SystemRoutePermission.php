<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;

/**
 * 接口路由与权限标识的映射.
 *
 * authority 指向 system_menu.authority（按钮菜单上的权限标识），
 * 不再单独维护权限点表，避免与菜单数据产生冗余和漂移。
 *
 * @property int $id ID
 * @property string $method HTTP方法
 * @property string $path 路由路径，动态段用 {id}/{type} 占位
 * @property null|string $authority 所需权限标识，is_public=1 时为 null
 * @property int $is_public 仅需登录即可访问：0-否，1-是
 * @property string $remark 备注
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class SystemRoutePermission extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_route_permission';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'method', 'path', 'authority', 'is_public', 'remark', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'is_public' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
