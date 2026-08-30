<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Exception\BusinessException;
use App\Model\Signature;
use App\Model\SystemAdmin;
use Hyperf\DbConnection\Db;

class SignatureLogic
{
    /**
     * 获取签名列表（分页）.
     */
    public function getSignatureList(array $params = []): array
    {
        $query = Signature::query();

        if (! empty($params['name']) && trim((string) $params['name']) !== '') {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = (int) ($params['page_size'] ?? 20);

        $total = $query->count();
        // 列表只返回轻量字段，签名图片仅在详情、编辑或签名选择器中按需读取。
        $list = $query
            ->forPage($page, $pageSize)
            ->get([
                'id',
                'name',
                'remark',
                'sort',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ])
            ->toArray();

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
            $item['created_by_name'] = $nameMap[$item['created_by']] ?? '';
            $item['updated_by_name'] = $nameMap[$item['updated_by']] ?? '';
        }
        unset($item);

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 启用状态的签名列表（单据表单里的签名选择器用）.
     */
    public function getEnabledSignatures(): array
    {
        return Signature::query()
            ->where('image_base64', '<>', '')
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'image_base64'])
            ->toArray();
    }

    /**
     * 获取签名详情.
     */
    public function getSignatureById(int $id): array
    {
        return $this->findOrFail($id)->toArray();
    }

    /**
     * 创建签名.
     */
    public function createSignature(array $data, int $adminId): array
    {
        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;

        return Db::transaction(static function () use ($data) {
            return Signature::query()->create($data)->toArray();
        });
    }

    /**
     * 更新签名.
     */
    public function updateSignature(int $id, array $data, int $adminId): array
    {
        $signature = $this->findOrFail($id);

        unset($data['created_by']);
        $data['updated_by'] = $adminId;

        return Db::transaction(static function () use ($signature, $data) {
            $signature->update($data);

            return $signature->toArray();
        });
    }

    /**
     * 删除签名.
     *
     * 单据里存的是图片 Base64 副本，删除签名不影响已签发的报告和证书。
     */
    public function deleteSignature(int $id): void
    {
        $this->findOrFail($id)->delete();
    }


    /**
     * 取签名实例，不存在抛异常.
     */
    protected function findOrFail(int $id): Signature
    {
        $signature = Signature::query()->find($id);

        if (! $signature instanceof Signature) {
            throw new BusinessException('签名不存在');
        }

        return $signature;
    }
}
