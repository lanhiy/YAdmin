<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;
/**
 * @property int $id ID
 * @property string $name 角色名称
 * @property string $code 角色编码
 * @property string $description 角色描述
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property-read null|Collection|SystemAdmin[] $admins 
 * @property-read null|Collection|SystemMenu[] $menus 
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
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 关联管理员 - 多对多
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(SystemAdmin::class,'system_admin_role','role_id','admin_id')->withTimestamps();
    }

    /**
     * 关联菜单
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(SystemMenu::class, 'system_role_menu', 'role_id', 'menu_id');
    }

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
}
