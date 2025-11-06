<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 *  * @link     https://github.com/G-YDG/HyperfAdminApi
 *  * @license  https://github.com/G-YDG/HyperfAdminApi/blob/master/LICENSE
 */

namespace App\System\Request;

use App\Model\SystemAdmin;
use Hyperf\Validation\Request\FormRequest;

class SystemAdminRequest extends FormRequest
{
    protected array $scenes = [
        'login' => ['username', 'password'],
        'save' => ['username', 'password', 'role_ids'],
        'update' => ['username', 'role_ids'],
        'changeStatus' => ['id', 'status'],
        'modifyUserInfo' => ['nickname', 'avatar', 'phone', 'email'],
        'modifyPassword' => ['oldPassword', 'newPassword', 'newPassword_confirmation'],
    ];

    /**
     * 验证规则.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'nickname' => 'required|string',
            'avatar' => 'required|url',
            'phone' => ['numeric', function ($attribute, $value, $fail) {
                if (!preg_match('/^(1[3-9])\d{9}$/', $value)) {
                    $fail('手机号格式有误');
                }
            }],
            'email' => 'email',
            'username' => 'required|min:5|max:20',
            'password' => 'required|min:5|max:20',
            'role_ids' => 'required',
            'oldPassword' => ['required', function ($attribute, $value, $fail) {
            }],
            'newPassword' => 'required|confirmed|min:5|max:20',
            'newPassword_confirmation' => 'required',
        ];
    }

    /**
     * 字段映射名称
     * return array.
     */
    public function attributes(): array
    {
        return [
            'id' => '用户ID',
            'username' => '用户名',
            'password' => '密码',
            'oldPassword' => '旧密码',
            'newPassword' => '新密码',
            'newPassword_confirmation' => '确认密码',
            'status' => '状态',
            'role_ids' => '角色',
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => '请填写用户昵称',
            'avatar.required' => '请上传头像',
            'avatar.url' => '头像链接格式有误',
            'phone.digits' => '手机号格式有误',
            'email.email' => '邮箱格式有误',
            'role_ids.required' => '请选择角色',
            'username.min' => '用户名长度最小为5位',
            'username.max' => '用户名最大长度为20位',
            'password.min' => '密码长度最小为5位',
            'password.max' => '密码长度最长为20位',
            'oldPassword.required' => '请输入旧密码',
            'newPassword.required' => '请输入新密码',
            'newPassword.min' => '密码长度最小为5位',
            'newPassword.confirmed' => '新密码输入不一致',
            'newPassword_confirmation.required' => '请确认新密码',
        ];
    }

    /**
     * ⭐ 关键：必须添加这个方法并返回 true
     * 确定用户是否有权发出此请求
     */
    public function authorize(): bool
    {
        return true;  // 返回 true 表示允许所有请求

    }
}