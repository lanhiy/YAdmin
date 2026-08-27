<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;

/**
 * 统一证书/报告表。
 *
 * certificate_type: 1 测试报告，2 检定证书，3 校准证书。
 * certificate_no 在接口层按类型映射为 report_no 或 cert_no。
 */
class ProductCertificate extends Model
{
    use SoftDeletes;

    public const TYPE_TEST_REPORT = 1;
    public const TYPE_VERIFICATION_CERT = 2;
    public const TYPE_CALIBRATION_CERT = 3;

    protected ?string $table = 'product_certificate';

    protected array $fillable = [
        'id',
        'product_id',
        'certificate_type',
        'certificate_no',
        'public_token',
        'client_name',
        'submit_unit',
        'basis',
        'conclusion',
        'address',
        'approver_sign_img',
        'reviewer_sign_img',
        'tester_sign_img',
        'verifier_sign_img',
        'calibrator_sign_img',
        'test_date',
        'verify_date',
        'valid_until',
        'receive_date',
        'calibrate_date',
        'issue_date',
        'total_pages',
        'remark',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'certificate_type' => 'integer',
        'total_pages' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'test_date' => 'date:Y-m-d',
        'verify_date' => 'date:Y-m-d',
        'valid_until' => 'date:Y-m-d',
        'receive_date' => 'date:Y-m-d',
        'calibrate_date' => 'date:Y-m-d',
        'issue_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(static function (ProductCertificate $certificate): void {
            if (! is_string($certificate->public_token) || $certificate->public_token === '') {
                $certificate->public_token = bin2hex(random_bytes(16));
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
