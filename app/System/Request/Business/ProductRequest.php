<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use Hyperf\Validation\Request\FormRequest;

class ProductRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['instrument_name', 'instrument_no', 'model', 'manufacturer', 'remark', 'sort'],
        'update' => ['instrument_name', 'instrument_no', 'model', 'manufacturer', 'remark', 'sort'],
        'changeStatus' => ['id'],
    ];

    /**
     * 验证规则.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'instrument_name' => 'required|string|max:100',
            'instrument_no' => 'required|string|max:100',
            'model' => 'string|max:100',
            'manufacturer' => 'string|max:150',
            'remark' => 'string|max:500',
            'sort' => 'integer',
        ];
    }

    /**
     * 字段映射名称.
     */
    public function attributes(): array
    {
        return [
            'id' => '产品ID',
            'instrument_name' => '器具名称',
            'instrument_no' => '器具编号',
            'model' => '型号',
            'manufacturer' => '制造厂商',
            'remark' => '备注',
            'sort' => '排序',
        ];
    }

    /**
     * 自定义错误消息.
     */
    public function messages(): array
    {
        return [
            'instrument_name.required' => '请输入器具名称',
            'instrument_name.max' => '器具名称最多100个字符',
            'instrument_no.required' => '请输入器具编号',
            'instrument_no.max' => '器具编号最多100个字符',
            'model.max' => '型号最多100个字符',
            'manufacturer.max' => '制造厂商最多150个字符',
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
