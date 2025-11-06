<?php

declare(strict_types=1);

namespace App\Model;



use Hyperf\Database\Model\SoftDeletes;
use Carbon\Carbon;

/**
 * @property int $id ID
 * @property string $username 账号
 * @property string $mobile 手机号
 * @property string $email 邮箱
 * @property string $nickname 昵称
 * @property int $gender 性别：0-未知，1-男，2-女
 * @property string $avatar 头像
 * @property int $status 状态：0-禁用，1-启用
 * @property int $sort 排序
 * @property string $last_login_at 最后登录时间
 * @property string $last_login_ip 最后登录IP
 * @property string $remark 备注
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property string $deleted_at 删除时间
 * @property-write mixed $password 密码
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
    protected array $fillable = ['id', 'username', 'mobile', 'email', 'password', 'nickname', 'gender', 'avatar', 'status', 'sort', 'last_login_at', 'last_login_ip', 'remark', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer', 'status' => 'integer', 'sort' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 验证密码
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function passwordVerify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 密码加密.
     * @param string $value
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }
}
