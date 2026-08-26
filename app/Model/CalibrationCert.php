<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;

/**
 * 校准证书表（与 product 一对一）.
 *
 * @property int $id ID
 * @property int $product_id 产品ID
 * @property string $cert_no 证书编号
 * @property string $client_name 委托方
 * @property string $unit_name 单位名称
 * @property string $address 地址
 * @property string $approver_sign_img 批准人签名图片
 * @property string $reviewer_sign_img 核验人签名图片
 * @property string $calibrator_sign_img 校准人签名图片
 * @property string $receive_date 接收日期
 * @property string $calibrate_date 校准日期
 * @property string $issue_date 签发日期
 * @property int $total_pages 总页数
 * @property string $remark 备注
 * @property int $status 状态：0-禁用，1-启用
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class CalibrationCert extends Model
{
    public const STATUS_DISABLED = 0;

    public const STATUS_ENABLED = 1;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'calibration_cert';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'product_id', 'cert_no', 'client_name', 'unit_name', 'address', 'approver_sign_img', 'reviewer_sign_img', 'calibrator_sign_img', 'receive_date', 'calibrate_date', 'issue_date', 'total_pages', 'remark', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'total_pages' => 'integer', 'status' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'receive_date' => 'date:Y-m-d', 'calibrate_date' => 'date:Y-m-d', 'issue_date' => 'date:Y-m-d', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 所属产品.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
