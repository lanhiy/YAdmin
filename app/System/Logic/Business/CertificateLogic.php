<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Exception\BusinessException;
use App\Model\Certificate;
use App\Model\SystemAdmin;
use App\System\Logic\ConfigLogic;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CertificateLogic
{
    #[Inject]
    protected ConfigLogic $configLogic;

    /**
     * 证书查询URL的配置键名
     */
    protected const CONFIG_KEY_QUERY_URL = 'certificate_query_url';

    /**
     * 拼接证书二维码地址（附加字段，不入库）
     * 规则：配置的查询URL + 证书专属查询码 query_key
     */
    protected function buildQrUrl(string $queryKey): string
    {
        if ($queryKey === '') {
            return '';
        }
        $baseUrl = (string)($this->configLogic->getConfigByKey(self::CONFIG_KEY_QUERY_URL) ?? '');
        if ($baseUrl === '') {
            return '';
        }
        return $baseUrl . $queryKey;
    }

    /**
     * 生成唯一的证书查询码
     */
    protected function generateUniqueQueryKey(int $length = 8): string
    {
        do {
            $key = generate_code($length);
        } while (Certificate::query()->where('query_key', $key)->exists());

        return $key;
    }

    /**
     * 获取证书列表（分页）
     */
    public function getCertificateList(array $params = []): array
    {
        $query = Certificate::query();

        // 搜索条件 - 只在值非空时才添加条件
        if (!empty($params['cert_no']) && trim($params['cert_no']) !== '') {
            $query->where('cert_no', 'like', '%' . $params['cert_no'] . '%');
        }
        if (!empty($params['unit_name']) && trim($params['unit_name']) !== '') {
            $query->where('unit_name', 'like', '%' . $params['unit_name'] . '%');
        }
        if (!empty($params['instrument_name']) && trim($params['instrument_name']) !== '') {
            $query->where('instrument_name', 'like', '%' . $params['instrument_name'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'desc');

        // 分页
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        // 批量补充创建人/更新人昵称
        $adminIds = [];
        foreach ($list as $item) {
            if (!empty($item['created_by'])) {
                $adminIds[] = $item['created_by'];
            }
            if (!empty($item['updated_by'])) {
                $adminIds[] = $item['updated_by'];
            }
        }

        $nameMap = [];
        if (!empty($adminIds)) {
            $nameMap = SystemAdmin::query()
                ->whereIn('id', array_unique($adminIds))
                ->pluck('nickname', 'id')
                ->toArray();
        }

        foreach ($list as &$item) {
            $item['created_by_name'] = $nameMap[$item['created_by']] ?? '';
            $item['updated_by_name'] = $nameMap[$item['updated_by']] ?? '';
            // 附加二维码地址（不入库）
            $item['qr_url'] = $this->buildQrUrl((string)($item['query_key'] ?? ''));
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
     * 根据ID获取证书
     */
    public function getCertificateById(int $id): array
    {
        $certificate = Certificate::query()->find($id);

        if (!$certificate instanceof Certificate) {
            throw new BusinessException('证书不存在');
        }

        $data = $certificate->toArray();
        // 附加二维码地址（不入库）
        $data['qr_url'] = $this->buildQrUrl((string)($data['query_key'] ?? ''));

        return $data;
    }

    /**
     * 创建证书
     */
    public function createCertificate(array $data, int $adminId): array
    {
        // 检查证书编号是否重复
        $exists = Certificate::query()
            ->where('cert_no', $data['cert_no'])
            ->exists();

        if ($exists) {
            throw new BusinessException('证书编号已存在');
        }

        // 设置默认值
        $data['sort'] = $data['sort'] ?? 0;
        $data['status'] = $data['status'] ?? Certificate::STATUS_ENABLED;
        $data['created_by'] = $adminId;
        $data['updated_by'] = $adminId;
        // 生成证书专属查询码（用于二维码）
        $data['query_key'] = $this->generateUniqueQueryKey();

        return Db::transaction(function () use ($data) {
            $certificate = Certificate::query()->create($data);
            return $certificate->toArray();
        });
    }

    /**
     * 更新证书
     */
    public function updateCertificate(int $id, array $data, int $adminId): array
    {
        $certificate = Certificate::query()->find($id);

        if (!$certificate instanceof Certificate) {
            throw new BusinessException('证书不存在');
        }

        // 检查证书编号是否重复
        $exists = Certificate::query()
            ->where('cert_no', $data['cert_no'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            throw new BusinessException('证书编号已存在');
        }

        // 禁止修改创建人和查询码，写入更新人
        unset($data['created_by'], $data['query_key']);
        $data['updated_by'] = $adminId;

        // 存量数据可能没有查询码，更新时补生成
        if (empty($certificate->query_key)) {
            $data['query_key'] = $this->generateUniqueQueryKey();
        }

        return Db::transaction(function () use ($certificate, $data) {
            $certificate->update($data);
            return $certificate->toArray();
        });
    }

    /**
     * 删除证书
     */
    public function deleteCertificate(int $id): void
    {
        $certificate = Certificate::query()->find($id);

        if (!$certificate instanceof Certificate) {
            throw new BusinessException('证书不存在');
        }

        $certificate->delete();
    }

    /**
     * 修改证书状态
     */
    public function changeStatus(int $id, int $status): void
    {
        $certificate = Certificate::query()->find($id);

        if (!$certificate instanceof Certificate) {
            throw new BusinessException('证书不存在');
        }

        if (!in_array($status, [Certificate::STATUS_DISABLED, Certificate::STATUS_ENABLED])) {
            throw new BusinessException('状态值不合法');
        }

        $certificate->status = $status;
        $certificate->save();
    }
}
