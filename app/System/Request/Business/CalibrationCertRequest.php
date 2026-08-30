<?php

declare(strict_types=1);

namespace App\System\Request\Business;

use App\Validation\Rules\Base64Image;
use Hyperf\Validation\Request\FormRequest;

class CalibrationCertRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['product_id', 'cert_no', 'client_name', 'address', 'approver_sign_img', 'reviewer_sign_img', 'calibrator_sign_img', 'receive_date', 'calibrate_date', 'issue_date', 'total_pages', 'remark'],
        'update' => ['cert_no', 'client_name', 'address', 'approver_sign_img', 'reviewer_sign_img', 'calibrator_sign_img', 'receive_date', 'calibrate_date', 'issue_date', 'total_pages', 'remark'],
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
            'client_name' => 'string|max:150',
            'address' => 'string|max:255',
            'approver_sign_img' => ['string', new Base64Image()],
            'reviewer_sign_img' => ['string', new Base64Image()],
            'calibrator_sign_img' => ['string', new Base64Image()],
            'receive_date' => 'required|date',
            'calibrate_date' => 'required|date',
            'issue_date' => 'required|date',
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
            'client_name' => '委托方',
            'address' => '地址',
            'approver_sign_img' => '批准人签名',
            'reviewer_sign_img' => '核验人签名',
            'calibrator_sign_img' => '校准人签名',
            'receive_date' => '接收日期',
            'calibrate_date' => '校准日期',
            'issue_date' => '签发日期',
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
            'client_name.max' => '委托方最多150个字符',
            'address.max' => '地址最多255个字符',
            'receive_date.required' => '请选择接收日期',
            'receive_date.date' => '接收日期格式不正确',
            'calibrate_date.required' => '请选择校准日期',
            'calibrate_date.date' => '校准日期格式不正确',
            'issue_date.required' => '请选择签发日期',
            'issue_date.date' => '签发日期格式不正确',
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
