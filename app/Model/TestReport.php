<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Database\Model\Relations\BelongsTo;

/**
 * 测试报告表（与 product 一对一）.
 *
 * @property int $id ID
 * @property int $product_id 产品ID
 * @property string $report_no 报告编号
 * @property string $client_name 委托方
 * @property string $unit_name 单位名称
 * @property string $approver_sign_img 批准人签名图片
 * @property string $reviewer_sign_img 核验人签名图片
 * @property string $tester_sign_img 测试人签名图片
 * @property string $test_date 测试日期
 * @property int $total_pages 总页数
 * @property string $remark 备注
 * @property null|\Carbon\Carbon $deleted_at 软删除时间
 * @property int $created_by 创建人ID
 * @property int $updated_by 更新人ID
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class TestReport extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'test_report';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'product_id', 'report_no', 'client_name', 'unit_name', 'approver_sign_img', 'reviewer_sign_img', 'tester_sign_img', 'test_date', 'total_pages', 'remark', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'product_id' => 'integer', 'total_pages' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer', 'test_date' => 'date:Y-m-d', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    /**
     * 所属产品.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
