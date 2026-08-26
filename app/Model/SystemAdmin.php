<?php

declare(strict_types=1);

namespace App\Model;



use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;
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
 * @property-write mixed $password 密码
 * @property-read null|Collection|SystemRole[] $roles
 */
class SystemAdmin extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_admin';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'username', 'mobile', 'email', 'password', 'nickname', 'gender', 'avatar', 'status', 'sort', 'last_login_at', 'last_login_ip', 'remark', 'created_at', 'updated_at'];

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

    /**
     * 关联角色 - 多对多
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SystemRole::class, 'system_admin_role', 'admin_id', 'role_id')->withTimestamps();
    }

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public const GENDER_UNKNOWN = 0;
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;

}
