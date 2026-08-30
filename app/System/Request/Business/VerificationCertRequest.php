<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use App\Validation\Rules\Base64Image;
use Hyperf\Validation\Request\FormRequest;

class VerificationCertRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['product_id', 'cert_no', 'submit_unit', 'basis', 'conclusion', 'approver_sign_img', 'reviewer_sign_img', 'verifier_sign_img', 'verify_date', 'valid_until', 'total_pages', 'remark'],
        'update' => ['cert_no', 'submit_unit', 'basis', 'conclusion', 'approver_sign_img', 'reviewer_sign_img', 'verifier_sign_img', 'verify_date', 'valid_until', 'total_pages', 'remark'],
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
            'cert_no' => 'required|string|max:100',
            'submit_unit' => 'string|max:150',
            'basis' => 'string|max:500',
            'conclusion' => 'string|max:255',
            'approver_sign_img' => ['string', new Base64Image()],
            'reviewer_sign_img' => ['string', new Base64Image()],
            'verifier_sign_img' => ['string', new Base64Image()],
            'verify_date' => 'required|date',
            'valid_until' => 'required|date',
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
            'id' => '证书ID',
            'product_id' => '产品',
            'cert_no' => '证书编号',
            'submit_unit' => '送检单位',
            'basis' => '检定依据',
            'conclusion' => '检定结论',
            'approver_sign_img' => '批准人签名',
            'reviewer_sign_img' => '核验人签名',
            'verifier_sign_img' => '检定人签名',
            'verify_date' => '检定日期',
            'valid_until' => '有效期',
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
            'cert_no.required' => '请输入证书编号',
            'cert_no.max' => '证书编号最多100个字符',
            'submit_unit.max' => '送检单位最多150个字符',
            'basis.max' => '检定依据最多500个字符',
            'conclusion.max' => '检定结论最多255个字符',
            'verify_date.required' => '请选择检定日期',
            'verify_date.date' => '检定日期格式不正确',
            'valid_until.required' => '请选择有效期',
            'valid_until.date' => '有效期格式不正确',
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
