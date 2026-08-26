<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use App\Validation\Rules\Base64Image;
use Hyperf\Validation\Request\FormRequest;

class TestReportRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['product_id', 'report_no', 'client_name', 'approver_sign_img', 'reviewer_sign_img', 'tester_sign_img', 'test_date', 'total_pages', 'remark'],
        'update' => ['report_no', 'client_name', 'approver_sign_img', 'reviewer_sign_img', 'tester_sign_img', 'test_date', 'total_pages', 'remark'],
        'changeStatus' => ['id'],
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
            'approver_sign_img' => ['string', new Base64Image()],
            'reviewer_sign_img' => ['string', new Base64Image()],
            'tester_sign_img' => ['string', new Base64Image()],
            'test_date' => 'required|date',
            'total_pages' => 'integer|min:1',
            'remark' => 'string|max:500',
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
            'approver_sign_img' => '批准人签名',
            'reviewer_sign_img' => '核验人签名',
            'tester_sign_img' => '测试人签名',
            'test_date' => '测试日期',
            'total_pages' => '总页数',
            'remark' => '备注',
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
            'test_date.required' => '请选择测试日期',
            'test_date.date' => '测试日期格式不正确',
            'total_pages.min' => '总页数至少为1',
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
