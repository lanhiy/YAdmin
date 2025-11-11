<?php

declare(strict_types=1);

namespace App\System\Request;

use Hyperf\Validation\Request\FormRequest;

class SystemConfigRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['config_key', 'config_value', 'config_type', 'description', 'sort', 'status'],
        'update' => ['config_key', 'config_value', 'config_type', 'description', 'sort', 'status'],
        'batchUpdate' => ['configs'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'config_key' => 'required|string|max:100',
            'config_value' => 'required',
            'config_type' => 'required|string|max:50',
            'description' => 'string|max:255',
            'sort' => 'integer',
            'status' => 'required|integer|in:0,1',
            'configs' => 'required|array',
        ];
    }

    /**
     * 字段映射名称
     */
    public function attributes(): array
    {
        return [
            'id' => '配置ID',
            'config_key' => '配置键名',
            'config_value' => '配置值',
            'config_type' => '配置类型',
            'description' => '配置描述',
            'sort' => '排序',
            'status' => '状态',
            'configs' => '配置数据',
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'config_key.required' => '请输入配置键名',
            'config_key.max' => '配置键名最多100个字符',
            'config_value.required' => '请输入配置值',
            'config_type.required' => '请选择配置类型',
            'config_type.max' => '配置类型最多50个字符',
            'description.max' => '配置描述最多255个字符',
            'status.required' => '请选择状态',
            'status.in' => '状态值不合法',
            'configs.required' => '配置数据不能为空',
            'configs.array' => '配置数据格式不正确',
        ];
    }

    /**
     * 授权验证
     */
    public function authorize(): bool
    {
        return true;
    }
}