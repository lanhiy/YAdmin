<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use Hyperf\Validation\Request\FormRequest;

class CertificateRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['cert_no', 'unit_name', 'instrument_name', 'model', 'factory_no', 'manufacturer', 'check_date', 'valid_until', 'check_unit', 'remark', 'sort', 'status'],
        'update' => ['cert_no', 'unit_name', 'instrument_name', 'model', 'factory_no', 'manufacturer', 'check_date', 'valid_until', 'check_unit', 'remark', 'sort', 'status'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'cert_no' => 'required|string|max:50',
            'unit_name' => 'required|string|max:100',
            'instrument_name' => 'required|string|max:100',
            'model' => 'string|max:100',
            'factory_no' => 'string|max:50',
            'manufacturer' => 'string|max:150',
            'check_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'check_unit' => 'string|max:150',
            'remark' => 'string|max:500',
            'sort' => 'integer',
            'status' => 'required|integer|in:0,1',
        ];
    }

    /**
     * 字段映射名称
     */
    public function attributes(): array
    {
        return [
            'id' => '证书ID',
            'cert_no' => '证书编号',
            'unit_name' => '单位名称',
            'instrument_name' => '器具名称',
            'model' => '型号规格',
            'factory_no' => '出厂编号',
            'manufacturer' => '制造厂商',
            'check_date' => '校检日期',
            'valid_until' => '有效期',
            'check_unit' => '校检单位',
            'remark' => '备注',
            'sort' => '排序',
            'status' => '状态',
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'cert_no.required' => '请输入证书编号',
            'cert_no.max' => '证书编号最多50个字符',
            'unit_name.required' => '请输入单位名称',
            'unit_name.max' => '单位名称最多100个字符',
            'instrument_name.required' => '请输入器具名称',
            'instrument_name.max' => '器具名称最多100个字符',
            'check_date.date' => '校检日期格式不正确',
            'valid_until.date' => '有效期格式不正确',
            'status.required' => '请选择状态',
            'status.in' => '状态值不合法',
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
