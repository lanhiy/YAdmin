<?php

declare(strict_types=1);

namespace App\Model;



use Hyperf\Database\Model\SoftDeletes;
use Carbon\Carbon;

/**
 * @property int $id ID
 * @property string $username 账号
 * @property string $password 密码
 * @property string $nickname 昵称
 * @property string $avatar 头像
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 */
class SystemAdmin extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_admin';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'username', 'password', 'nickname', 'avatar', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
