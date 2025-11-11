<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemConfig;
use Hyperf\DbConnection\Db;

class ConfigLogic
{
    /**
     * 获取所有配置（给前端初始化用）
     */
    public function getAllConfig(): array
    {
        $configs = SystemConfig::query()
            ->where('status', SystemConfig::STATUS_ENABLED)
            ->orderBy('sort', 'asc')
            ->get(['config_key', 'config_value', 'config_type'])
            ->toArray();

        $result = [];
        foreach ($configs as $config) {
            // 解码JSON值
            $value = json_decode($config['config_value'], true);
            // 如果解码失败，保持原值
            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = $config['config_value'];
            }

            $result[$config['config_key']] = $value;
        }

        return $result;
    }

    /**
     * 获取配置列表（分页，后台管理用）
     */
    public function getConfigList(array $params = []): array
    {
        $query = SystemConfig::query();

        // 搜索条件
        if (!empty($params['config_key'])) {
            $query->where('config_key', 'like', '%' . $params['config_key'] . '%');
        }
        if (!empty($params['config_type'])) {
            $query->where('config_type', $params['config_type']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        $query->orderBy('sort', 'asc')->orderBy('id', 'asc');

        // 分页
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);

        $total = $query->count();
        $list = $query->forPage($page, $pageSize)->get()->toArray();

        // 解码配置值
        foreach ($list as &$item) {
            $value = json_decode($item['config_value'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $item['config_value_decoded'] = $value;
            }
        }

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 按类型获取配置
     */
    public function getConfigByType(string $type): array
    {
        $configs = SystemConfig::query()
            ->where('config_type', $type)
            ->where('status', SystemConfig::STATUS_ENABLED)
            ->orderBy('sort', 'asc')
            ->get(['config_key', 'config_value'])
            ->toArray();

        $result = [];
        foreach ($configs as $config) {
            $value = json_decode($config['config_value'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = $config['config_value'];
            }
            $result[$config['config_key']] = $value;
        }

        return $result;
    }

    /**
     * 根据ID获取配置
     */
    public function getConfigById(int $id): array
    {
        $config = SystemConfig::query()->find($id);

        if (!$config instanceof SystemConfig) {
            throw new BusinessException('配置不存在');
        }

        $data = $config->toArray();

        // 解码配置值
        $value = json_decode($data['config_value'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data['config_value_decoded'] = $value;
        }

        return $data;
    }

    /**
     * 根据配置键获取配置
     */
    public function getConfigByKey(string $key): mixed
    {
        $config = SystemConfig::query()
            ->where('config_key', $key)
            ->where('status', SystemConfig::STATUS_ENABLED)
            ->first();

        if (!$config instanceof SystemConfig) {
            return null;
        }

        $value = json_decode($config->config_value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $config->config_value;
        }

        return $value;
    }

    /**
     * 创建配置
     */
    public function createConfig(array $data): array
    {
        // 检查配置键是否重复
        $exists = SystemConfig::query()
            ->where('config_key', $data['config_key'])
            ->exists();

        if ($exists) {
            throw new BusinessException('配置键已存在');
        }

        // 编码配置值
        if (isset($data['config_value'])) {
            $data['config_value'] = json_encode($data['config_value'], JSON_UNESCAPED_UNICODE);
        }

        // 设置默认值
        $data['sort'] = $data['sort'] ?? 0;
        $data['status'] = $data['status'] ?? SystemConfig::STATUS_ENABLED;

        $config = SystemConfig::query()->create($data);

        return $config->toArray();
    }

    /**
     * 更新配置
     */
    public function updateConfig(int $id, array $data): array
    {
        $config = SystemConfig::query()->find($id);

        if (!$config instanceof SystemConfig) {
            throw new BusinessException('配置不存在');
        }

        // 检查配置键是否重复
        if (isset($data['config_key'])) {
            $exists = SystemConfig::query()
                ->where('config_key', $data['config_key'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw new BusinessException('配置键已存在');
            }
        }

        // 编码配置值
        if (isset($data['config_value'])) {
            $data['config_value'] = json_encode($data['config_value'], JSON_UNESCAPED_UNICODE);
        }

        $config->update($data);

        return $config->toArray();
    }

    /**
     * 批量更新配置
     */
    public function batchUpdateConfig(array $configs): void
    {
        Db::transaction(function () use ($configs) {
            foreach ($configs as $key => $value) {
                $config = SystemConfig::query()
                    ->where('config_key', $key)
                    ->first();

                if ($config instanceof SystemConfig) {
                    $config->config_value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    $config->save();
                }
            }
        });
    }

    /**
     * 删除配置
     */
    public function deleteConfig(int $id): void
    {
        $config = SystemConfig::query()->find($id);

        if (!$config instanceof SystemConfig) {
            throw new BusinessException('配置不存在');
        }

        $config->delete();
    }

    /**
     * 修改配置状态
     */
    public function changeStatus(int $id, int $status): void
    {
        $config = SystemConfig::query()->find($id);

        if (!$config instanceof SystemConfig) {
            throw new BusinessException('配置不存在');
        }

        if (!in_array($status, [SystemConfig::STATUS_DISABLED, SystemConfig::STATUS_ENABLED])) {
            throw new BusinessException('状态值不合法');
        }

        $config->status = $status;
        $config->save();
    }
}