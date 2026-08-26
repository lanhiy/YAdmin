<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use Hyperf\Validation\Request\FormRequest;

class TestReportRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['product_id', 'report_no', 'client_name', 'unit_name', 'approver_sign_img', 'reviewer_sign_img', 'tester_sign_img', 'test_date', 'total_pages', 'remark', 'status'],
        'update' => ['report_no', 'client_name', 'unit_name', 'approver_sign_img', 'reviewer_sign_img', 'tester_sign_img', 'test_date', 'total_pages', 'remark', 'status'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则.
     *
     * 日期字段库里是 NOT NULL，这里一律 required。
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'product_id' => 'required|integer|min:1',
            'report_no' => 'required|string|max:50',
            'client_name' => 'string|max:150',
            'unit_name' => 'string|max:150',
            'approver_sign_img' => 'string|max:255',
            'reviewer_sign_img' => 'string|max:255',
            'tester_sign_img' => 'string|max:255',
            'test_date' => 'required|date',
            'total_pages' => 'integer|min:1',
            'remark' => 'string|max:500',
            'status' => 'required|integer|in:0,1',
        ];
    }

    /**
     * 字段映射名称.
     */
    public function attributes(): array
    {
        return [
            'id' => '报告ID',
            'product_id' => '产品',
            'report_no' => '报告编号',
            'client_name' => '委托方',
            'unit_name' => '单位名称',
            'approver_sign_img' => '批准人签名',
            'reviewer_sign_img' => '核验人签名',
            'tester_sign_img' => '测试人签名',
            'test_date' => '测试日期',
            'total_pages' => '总页数',
            'remark' => '备注',
            'status' => '状态',
        ];
    }

    /**
     * 自定义错误消息.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => '请选择产品',
            'product_id.min' => '产品不合法',
            'report_no.required' => '请输入报告编号',
            'report_no.max' => '报告编号最多50个字符',
            'client_name.max' => '委托方最多150个字符',
            'unit_name.max' => '单位名称最多150个字符',
            'test_date.required' => '请选择测试日期',
            'test_date.date' => '测试日期格式不正确',
            'total_pages.min' => '总页数至少为1',
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
