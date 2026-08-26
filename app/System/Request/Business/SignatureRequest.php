<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use Hyperf\Validation\Request\FormRequest;

class SignatureRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['name', 'image_url', 'remark', 'sort', 'status'],
        'update' => ['name', 'image_url', 'remark', 'sort', 'status'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'name' => 'required|string|max:50',
            'image_url' => 'required|string|max:255',
            'remark' => 'string|max:500',
            'sort' => 'integer',
            'status' => 'required|integer|in:0,1',
        ];
    }

    /**
     * 字段映射名称.
     */
    public function attributes(): array
    {
        return [
            'id' => '签名ID',
            'name' => '签名人姓名',
            'image_url' => '签名图片',
            'remark' => '备注',
            'sort' => '排序',
            'status' => '状态',
        ];
    }

    /**
     * 自定义错误消息.
     */
    public function messages(): array
    {
        return [
            'name.required' => '请输入签名人姓名',
            'name.max' => '签名人姓名最多50个字符',
            'image_url.required' => '请上传签名图片',
            'image_url.max' => '签名图片地址最多255个字符',
            'status.required' => '请选择状态',
            'status.in' => '状态值不合法',
        ];
    }

    /**
     * 授权验证.
     */
    public function authorize(): bool
    {
        return true;
    }
}
