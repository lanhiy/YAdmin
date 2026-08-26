<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;

/**
 * 预存签名图片库.
 *
 * 单据表里的签名字段只复制 image_url，不做外键关联，
 * 这样签名库改图/删除不会影响已签发的报告和证书。
 *
 * @property int $id ID
 * @property string $name 签名人姓名
 * @property string $image_url 签名图片地址
 * @property string $remark 备注
 * @property int $sort 排序
 * @property null|\Carbon\Carbon $deleted_at 软删除时间
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class Signature extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'signature';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'image_url', 'remark', 'sort', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];
}
