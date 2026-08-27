<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Exception\BusinessException;
use App\Model\Product;
use App\Model\ProductCertificate;
use App\Model\SystemAdmin;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

/**
 * 产品单据（测试报告/检定证书/校准证书）通用逻辑.
 *
 * 三张子表与 product 都是一对一，CRUD 流程完全一致，
 * 差异只有：模型类、业务编号字段、单据名称。子类只需声明这三项。
 */
abstract class ProductDocLogic
{
    /**
     * 单据模型类名.
     *
     * @return class-string<\App\Model\Model>
     */
    abstract protected function modelClass(): string;

    /**
     * 业务编号字段名（report_no / cert_no）.
     */
    abstract protected function noField(): string;

    /**
     * 单据名称，用于错误提示（测试报告 / 检定证书 / 校准证书）.
     */
    abstract protected function docName(): string;

    /** 统一证书表中的类型值。 */
    abstract protected function certificateType(): int;

    /**
     * 按产品ID获取单据，不存在返回 null（前端据此决定新增还是编辑）.
     */
    public function getByProductId(int $productId): ?array
    {
        $doc = $this->query()->where('product_id', $productId)->first();

        return $doc === null ? null : $this->toApiData($this->withExtra($doc->toArray()));
    }

    /**
     * 按单据ID获取详情.
     */
    public function getById(int $id): array
    {
        return $this->toApiData($this->withExtra($this->findOrFail($id)->toArray()));
    }

    /**
     * 新增单据.
     */
    public function create(array $data, int $adminId): array
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $this->assertProductExists($productId);

        // 一对一：同一产品只能有一份
        if ($this->query()->where('product_id', $productId)->exists()) {
            throw new BusinessException('该产品已存在' . $this->docName() . '，请直接编辑');
        }

        $this->assertNoUnique((string) ($data[$this->noField()] ?? ''));

        $data = $this->fromApiData($data);
        $data['certificate_type'] = $this->certificateType();

        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;

        return Db::transaction(function () use ($data) {
            $model = $this->modelClass();

            return $this->toApiData($this->withExtra($model::query()->create($data)->toArray()));
        });
    }

    /**
     * 更新单据.
     */
    public function update(int $id, array $data, int $adminId): array
    {
        $doc = $this->findOrFail($id);

        $this->assertNoUnique((string) ($data[$this->noField()] ?? ''), $id);

        $data = $this->fromApiData($data);

        // 产品归属和创建人不允许改
        unset($data['product_id'], $data['created_by']);
        $data['updated_by'] = $adminId;

        return Db::transaction(function () use ($doc, $data) {
            $doc->update($data);

            return $this->toApiData($this->withExtra($doc->refresh()->toArray()));
        });
    }

    /**
     * 删除单据.
     */
    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }


    /**
     * 查询构造器.
     */
    protected function query(): Builder
    {
        $model = $this->modelClass();

        return $model::query()->where('certificate_type', $this->certificateType());
    }

    /**
     * 取单据实例，不存在抛异常.
     */
    protected function findOrFail(int $id): \App\Model\Model
    {
        $doc = $this->query()->find($id);

        if ($doc === null) {
            throw new BusinessException($this->docName() . '不存在');
        }

        return $doc;
    }

    /**
     * 校验产品存在.
     */
    protected function assertProductExists(int $productId): void
    {
        if ($productId <= 0 || ! Product::query()->where('id', $productId)->exists()) {
            throw new BusinessException('产品不存在');
        }
    }

    /**
     * 校验业务编号唯一.
     */
    protected function assertNoUnique(string $no, ?int $exceptId = null): void
    {
        if ($no === '') {
            return;
        }

        $query = $this->query()->where('certificate_no', $no);

        if ($exceptId !== null) {
            $query->where('id', '<>', $exceptId);
        }

        if ($query->exists()) {
            throw new BusinessException($this->docName() . '编号已存在');
        }
    }

    /**
     * 补充产品信息和创建人/更新人昵称.
     */
    protected function withExtra(array $data): array
    {
        $product = Product::query()->find((int) ($data['product_id'] ?? 0));

        $data['product'] = $product === null ? null : [
            'id' => $product->id,
            'instrument_name' => $product->instrument_name,
            'instrument_no' => $product->instrument_no,
            'model' => $product->model,
            'manufacturer' => $product->manufacturer,
        ];

        $ids = array_filter([(int) ($data['created_by'] ?? 0), (int) ($data['updated_by'] ?? 0)]);
        $nameMap = $ids === []
            ? []
            : SystemAdmin::query()->whereIn('id', array_unique($ids))->pluck('nickname', 'id')->toArray();

        $data['created_by_name'] = $nameMap[$data['created_by']] ?? '';
        $data['updated_by_name'] = $nameMap[$data['updated_by']] ?? '';

        return $data;
    }

    /** 将统一表字段映射回原有三类接口字段。 */
    protected function toApiData(array $data): array
    {
        $data[$this->noField()] = (string) ($data['certificate_no'] ?? '');
        unset($data['certificate_no'], $data['certificate_type']);

        return $data;
    }

    /** 将原有接口字段转换为统一表字段。 */
    protected function fromApiData(array $data): array
    {
        $data['certificate_no'] = (string) ($data[$this->noField()] ?? '');
        // 公开令牌由服务端生成且不可通过业务表单修改。
        unset($data['report_no'], $data['cert_no'], $data['id'], $data['certificate_type'], $data['public_token']);

        return $data;
    }
}
