<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasOne;

/**
 * 产品（器具）主表.
 *
 * @property int $id ID
 * @property string $instrument_name 器具名称
 * @property string $instrument_no 器具编号
 * @property string $model 型号
 * @property string $manufacturer 制造厂商
 * @property string $unit_name 单位名称
 * @property string $remark 备注
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property null|TestReport $testReport 测试报告
 * @property null|VerificationCert $verificationCert 检定证书
 * @property null|CalibrationCert $calibrationCert 校准证书
 */
class Product extends Model
{
    public const STATUS_DISABLED = 0;

    public const STATUS_ENABLED = 1;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'product';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'instrument_name', 'instrument_no', 'model', 'manufacturer', 'unit_name', 'remark', 'sort', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 测试报告（一对一）.
     */
    public function testReport(): HasOne
    {
        return $this->hasOne(TestReport::class, 'product_id', 'id');
    }

    /**
     * 检定证书（一对一）.
     */
    public function verificationCert(): HasOne
    {
        return $this->hasOne(VerificationCert::class, 'product_id', 'id');
    }

    /**
     * 校准证书（一对一）.
     */
    public function calibrationCert(): HasOne
    {
        return $this->hasOne(CalibrationCert::class, 'product_id', 'id');
    }
}
