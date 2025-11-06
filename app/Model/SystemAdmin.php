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
 * @property string $deleted_at 删除时间
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
    protected array $fillable = ['id', 'username', 'mobile', 'email', 'password', 'nickname', 'gender', 'avatar', 'status', 'sort', 'last_login_at', 'last_login_ip', 'remark', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer', 'status' => 'integer', 'sort' => 'integer','last_login_at'=>'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 软删除默认值（表示未删除）
     */
    const DELETED_AT_DEFAULT = '1000-01-01 00:00:00';

    /**
     * 模型的默认属性值
     */
    protected array $attributes = [
        'deleted_at' => self::DELETED_AT_DEFAULT,
    ];

    /**
     * 模型启动
     */
    protected function boot(): void
    {
        parent::boot();

        // 替换默认的软删除全局作用域
        static::addGlobalScope('customSoftDelete', function (Builder $builder) {
            $builder->where('deleted_at', self::DELETED_AT_DEFAULT);
        });
    }

    /**
     * 重写软删除执行方法
     */
    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);
    }

    /**
     * 重写恢复方法
     */
    public function restore()
    {
        if ($event = $this->fireModelEvent('restoring')) {
            if (method_exists($event, 'isPropagationStopped') && $event->isPropagationStopped()) {
                return false;
            }
        }

        // 恢复为默认时间
        $this->{$this->getDeletedAtColumn()} = Carbon::parse(self::DELETED_AT_DEFAULT);

        $this->exists = true;
        $result = $this->save();

        $this->fireModelEvent('restored');

        return $result;
    }

    /**
     * 重写判断是否已删除
     */
    public function trashed(): bool
    {
        $deletedAt = $this->{$this->getDeletedAtColumn()};

        if (is_null($deletedAt)) {
            return false;
        }

        // 如果删除时间不等于默认值，说明已删除
        return !$deletedAt->equalTo(Carbon::parse(self::DELETED_AT_DEFAULT));
    }

    /**
     * 查询作用域 - 包括已删除的
     */
    public function scopeWithTrashed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('customSoftDelete');
    }

    /**
     * 查询作用域 - 只查询已删除的
     */
    public function scopeOnlyTrashed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('customSoftDelete')
            ->where('deleted_at', '>', self::DELETED_AT_DEFAULT);
    }

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
        return $this->belongsToMany(SystemRole::class,'system_admin_role','admin_id','role_id')->withTimestamps();
    }

}
