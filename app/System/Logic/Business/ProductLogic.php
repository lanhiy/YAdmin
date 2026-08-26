<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Exception\BusinessException;
use App\Model\CalibrationCert;
use App\Model\Product;
use App\Model\SystemAdmin;
use App\Model\TestReport;
use App\Model\VerificationCert;
use Hyperf\DbConnection\Db;

class ProductLogic
{
    /**
     * 获取产品列表（分页）.
     *
     * 附加 has_test_report / has_verification_cert / has_calibration_cert，
     * 供列表操作栏显示「已录入 / 未录入」。
     */
    public function getProductList(array $params = []): array
    {
        $query = Product::query();

        if (! empty($params['instrument_name']) && trim((string) $params['instrument_name']) !== '') {
            $query->where('instrument_name', 'like', '%' . $params['instrument_name'] . '%');
        }
        if (! empty($params['instrument_no']) && trim((string) $params['instrument_no']) !== '') {
            $query->where('instrument_no', 'like', '%' . $params['instrument_no'] . '%');
        }
        if (! empty($params['model']) && trim((string) $params['model']) !== '') {
            $query->where('model', 'like', '%' . $params['model'] . '%');
        }
        if (! empty($params['manufacturer']) && trim((string) $params['manufacturer']) !== '') {
            $query->where('manufacturer', 'like', '%' . $params['manufacturer'] . '%');
        }
        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = (int) ($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        return [
            'list' => $this->appendListExtra($list),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 产品下拉选项（新增单据时选产品用）.
     */
    public function getProductOptions(): array
    {
        return Product::query()
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get(['id', 'instrument_name', 'instrument_no', 'model'])
            ->toArray();
    }

    /**
     * 获取产品详情（带三张子表数据）.
     */
    public function getProductById(int $id): array
    {
        $product = $this->findOrFail($id);

        $data = $product->toArray();
        $data['test_report'] = TestReport::query()->where('product_id', $id)->first()?->toArray();
        $data['verification_cert'] = VerificationCert::query()->where('product_id', $id)->first()?->toArray();
        $data['calibration_cert'] = CalibrationCert::query()->where('product_id', $id)->first()?->toArray();
        $data['has_test_report'] = $data['test_report'] !== null;
        $data['has_verification_cert'] = $data['verification_cert'] !== null;
        $data['has_calibration_cert'] = $data['calibration_cert'] !== null;

        return $data;
    }

    /**
     * 创建产品.
     */
    public function createProduct(array $data, int $adminId): array
    {
        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;

        return Db::transaction(static function () use ($data) {
            return Product::query()->create($data)->toArray();
        });
    }

    /**
     * 复制产品及其已有业务单据。
     *
     * 单据编号必须保持唯一，因此复制时为报告/证书编号追加副本时间；
     * 器具编号不是唯一字段，复制产品时保留原编号。
     */
    public function copyProduct(int $id, int $adminId): array
    {
        $source = $this->findOrFail($id);
        $suffix = date('YmdHisv');

        $newProduct = Db::transaction(function () use ($source, $adminId, $suffix): Product {
            $newProduct = Product::query()->create([
                'instrument_name' => $this->appendCopySuffix((string) $source->instrument_name, $suffix, 100),
                'instrument_no' => $source->instrument_no,
                'model' => $source->model,
                'manufacturer' => $source->manufacturer,
                'remark' => $source->remark,
                'sort' => $source->sort,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            $productId = (int) $newProduct->id;

            $testReport = TestReport::query()->where('product_id', $source->id)->first();
            if ($testReport !== null) {
                TestReport::query()->create([
                    'product_id' => $productId,
                    'report_no' => $this->appendCopySuffix((string) $testReport->report_no, $suffix, 50, true),
                    'client_name' => $testReport->client_name,
                    'approver_sign_img' => $testReport->approver_sign_img,
                    'reviewer_sign_img' => $testReport->reviewer_sign_img,
                    'tester_sign_img' => $testReport->tester_sign_img,
                    'test_date' => $testReport->test_date,
                    'total_pages' => $testReport->total_pages,
                    'remark' => $testReport->remark,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }

            $verificationCert = VerificationCert::query()->where('product_id', $source->id)->first();
            if ($verificationCert !== null) {
                VerificationCert::query()->create([
                    'product_id' => $productId,
                    'cert_no' => $this->appendCopySuffix((string) $verificationCert->cert_no, $suffix, 50, true),
                    'submit_unit' => $verificationCert->submit_unit,
                    'basis' => $verificationCert->basis,
                    'conclusion' => $verificationCert->conclusion,
                    'approver_sign_img' => $verificationCert->approver_sign_img,
                    'reviewer_sign_img' => $verificationCert->reviewer_sign_img,
                    'verifier_sign_img' => $verificationCert->verifier_sign_img,
                    'verify_date' => $verificationCert->verify_date,
                    'valid_until' => $verificationCert->valid_until,
                    'total_pages' => $verificationCert->total_pages,
                    'remark' => $verificationCert->remark,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }

            $calibrationCert = CalibrationCert::query()->where('product_id', $source->id)->first();
            if ($calibrationCert !== null) {
                CalibrationCert::query()->create([
                    'product_id' => $productId,
                    'cert_no' => $this->appendCopySuffix((string) $calibrationCert->cert_no, $suffix, 50, true),
                    'client_name' => $calibrationCert->client_name,
                    'address' => $calibrationCert->address,
                    'approver_sign_img' => $calibrationCert->approver_sign_img,
                    'reviewer_sign_img' => $calibrationCert->reviewer_sign_img,
                    'calibrator_sign_img' => $calibrationCert->calibrator_sign_img,
                    'receive_date' => $calibrationCert->receive_date,
                    'calibrate_date' => $calibrationCert->calibrate_date,
                    'issue_date' => $calibrationCert->issue_date,
                    'total_pages' => $calibrationCert->total_pages,
                    'remark' => $calibrationCert->remark,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }

            return $newProduct;
        });

        return $this->getProductById((int) $newProduct->id);
    }

    /**
     * 更新产品.
     */
    public function updateProduct(int $id, array $data, int $adminId): array
    {
        $product = $this->findOrFail($id);

        unset($data['created_by']);
        $data['updated_by'] = $adminId;

        return Db::transaction(static function () use ($product, $data) {
            $product->update($data);

            return $product->toArray();
        });
    }

    /**
     * 删除产品，同时级联删除三张子表数据.
     */
    public function deleteProduct(int $id): void
    {
        $product = $this->findOrFail($id);

        // 三张子表与产品都启用了软删除，这里的 delete() 均为标记删除，
        // 数据可追溯；子表的全局作用域会自动过滤已删除记录。
        Db::transaction(static function () use ($product, $id) {
            TestReport::query()->where('product_id', $id)->delete();
            VerificationCert::query()->where('product_id', $id)->delete();
            CalibrationCert::query()->where('product_id', $id)->delete();
            $product->delete();
        });
    }


    /**
     * 批量补充创建人/更新人昵称与三张子表的录入标记.
     */
    protected function appendListExtra(array $list): array
    {
        if ($list === []) {
            return $list;
        }

        $productIds = array_column($list, 'id');

        $reportIds = TestReport::query()->whereIn('product_id', $productIds)->pluck('id', 'product_id')->toArray();
        $verifyIds = VerificationCert::query()->whereIn('product_id', $productIds)->pluck('id', 'product_id')->toArray();
        $calibIds = CalibrationCert::query()->whereIn('product_id', $productIds)->pluck('id', 'product_id')->toArray();

        $adminIds = [];
        foreach ($list as $item) {
            if (! empty($item['created_by'])) {
                $adminIds[] = $item['created_by'];
            }
            if (! empty($item['updated_by'])) {
                $adminIds[] = $item['updated_by'];
            }
        }

        $nameMap = $adminIds === []
            ? []
            : SystemAdmin::query()->whereIn('id', array_unique($adminIds))->pluck('nickname', 'id')->toArray();

        foreach ($list as &$item) {
            $id = $item['id'];
            $item['created_by_name'] = $nameMap[$item['created_by']] ?? '';
            $item['updated_by_name'] = $nameMap[$item['updated_by']] ?? '';
            $item['test_report_id'] = (int) ($reportIds[$id] ?? 0);
            $item['verification_cert_id'] = (int) ($verifyIds[$id] ?? 0);
            $item['calibration_cert_id'] = (int) ($calibIds[$id] ?? 0);
            $item['has_test_report'] = isset($reportIds[$id]);
            $item['has_verification_cert'] = isset($verifyIds[$id]);
            $item['has_calibration_cert'] = isset($calibIds[$id]);
        }
        unset($item);

        return $list;
    }

    /**
     * 取产品实例，不存在抛异常.
     */
    protected function findOrFail(int $id): Product
    {
        $product = Product::query()->find($id);

        if (! $product instanceof Product) {
            throw new BusinessException('产品不存在');
        }

        return $product;
    }

    private function appendCopySuffix(string $value, string $suffix, int $maxLength, bool $withSeparator = false): string
    {
        $append = ($withSeparator ? '-' : '') . '副本' . $suffix;
        $available = max(0, $maxLength - (function_exists('mb_strlen') ? mb_strlen($append) : strlen($append)));

        return (function_exists('mb_substr') ? mb_substr($value, 0, $available) : substr($value, 0, $available)) . $append;
    }
}
