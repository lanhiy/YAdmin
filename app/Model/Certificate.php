<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;

/**
 * @property int $id ID
 * @property string $cert_no 证书编号
 * @property string $query_key 证书查询专属码（二维码用）
 * @property string $unit_name 单位名称
 * @property string $instrument_name 器具名称
 * @property string $model 型号规格
 * @property string $factory_no 出厂编号
 * @property string $manufacturer 制造厂商
 * @property string $check_date 校检日期
 * @property string $valid_until 有效期
 * @property string $check_unit 校检单位
 * @property string $remark 备注
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class Certificate extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'certificate';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'cert_no', 'query_key', 'unit_name', 'instrument_name', 'model', 'factory_no', 'manufacturer', 'check_date', 'valid_until', 'check_unit', 'remark', 'sort', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'check_date' => 'date:Y-m-d', 'valid_until' => 'date:Y-m-d', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
}
