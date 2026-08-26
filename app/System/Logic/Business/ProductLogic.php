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
        $this->assertInstrumentNoUnique((string) ($data['instrument_no'] ?? ''));

        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;

        return Db::transaction(static function () use ($data) {
            return Product::query()->create($data)->toArray();
        });
    }

    /**
     * 更新产品.
     */
    public function updateProduct(int $id, array $data, int $adminId): array
    {
        $product = $this->findOrFail($id);

        $this->assertInstrumentNoUnique((string) ($data['instrument_no'] ?? ''), $id);

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

    /**
     * 校验器具编号唯一.
     */
    protected function assertInstrumentNoUnique(string $no, ?int $exceptId = null): void
    {
        if ($no === '') {
            return;
        }

        $query = Product::query()->where('instrument_no', $no);

        if ($exceptId !== null) {
            $query->where('id', '<>', $exceptId);
        }

        if ($query->exists()) {
            throw new BusinessException('器具编号已存在');
        }
    }
}
