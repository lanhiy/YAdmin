<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Database\Model\Relations\BelongsTo;

/**
 * 检定证书表（与 product 一对一）.
 *
 * @property int $id ID
 * @property int $product_id 产品ID
 * @property string $cert_no 证书编号
 * @property string $submit_unit 送检单位
 * @property string $basis 检定依据
 * @property string $conclusion 检定结论
 * @property string $approver_sign_img 批准人签名图片 Base64 Data URL
 * @property string $reviewer_sign_img 核验人签名图片 Base64 Data URL
 * @property string $verifier_sign_img 检定人签名图片 Base64 Data URL
 * @property string $verify_date 检定日期
 * @property string $valid_until 有效期
 * @property int $total_pages 总页数
 * @property string $remark 备注
 * @property null|\Carbon\Carbon $deleted_at 软删除时间
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class VerificationCert extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'verification_cert';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'product_id', 'cert_no', 'submit_unit', 'basis', 'conclusion', 'approver_sign_img', 'reviewer_sign_img', 'verifier_sign_img', 'verify_date', 'valid_until', 'total_pages', 'remark', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'total_pages' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'verify_date' => 'date:Y-m-d', 'valid_until' => 'date:Y-m-d', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    /**
     * 所属产品.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
