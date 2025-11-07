<?php

declare(strict_types=1);

namespace App\System\Request;

use Hyperf\Validation\Request\FormRequest;

class SystemAdminRequest extends FormRequest
{
    protected array $scenes = [
        'login' => ['username', 'password'],
        'save' => ['username', 'password', 'nickname', 'mobile', 'email', 'gender', 'status', 'sort', 'remark', 'role_ids'],
        'update' => ['username', 'nickname', 'mobile', 'email', 'gender', 'status', 'sort', 'remark', 'role_ids', 'password'], // password 可选
        'changeStatus' => ['id', 'status'],
        'modifyUserInfo' => ['nickname', 'avatar', 'mobile', 'email'], // 统一用 mobile
        'modifyPassword' => ['oldPassword', 'newPassword', 'newPassword_confirmation'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        // 获取当前场景
        $scene = $this->getScene();

        return [
            // 基础字段
            'id' => 'required|integer',
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|url',
            'mobile' => ['nullable', 'regex:/^1[3-9]\d{9}$/'], // 统一使用 mobile
            'email' => 'nullable|email|max:100',
            'gender' => 'nullable|integer|in:0,1,2', // 0:未知, 1:男, 2:女
            'sort' => 'nullable|integer|min:0',
            'remark' => 'nullable|string|max:500',

            // 账号相关
            'username' => 'required|string|min:4|max:20|regex:/^[a-zA-Z0-9_]+$/',
            'password' => $scene === 'save' ? 'required|string|min:5|max:50' : 'nullable|string|min:5|max:50', // 创建时必填，更新时选填
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:system_role,id', // 验证角色是否存在

            // 状态
            'status' => 'required|integer|in:0,1', // 0:禁用, 1:启用

            // 密码修改
            'oldPassword' => 'required|string',
            'newPassword' => 'required|string|confirmed|min:5|max:50|different:oldPassword',
            'newPassword_confirmation' => 'required|string',
        ];
    }

    /**
     * 字段映射名称
     */
    public function attributes(): array
    {
        return [
            'id' => '用户ID',
            'username' => '用户名',
            'password' => '密码',
            'nickname' => '用户昵称',
            'avatar' => '头像',
            'mobile' => '手机号',
            'email' => '邮箱',
            'gender' => '性别',
            'sort' => '排序',
            'remark' => '备注',
            'oldPassword' => '旧密码',
            'newPassword' => '新密码',
            'newPassword_confirmation' => '确认密码',
            'status' => '状态',
            'role_ids' => '角色',
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            // 用户名
            'username.required' => '请输入用户名',
            'username.min' => '用户名长度为4-20位',
            'username.max' => '用户名长度为4-20位',
            'username.regex' => '用户名只能包含字母、数字和下划线',

            // 密码
            'password.required' => '请输入密码',
            'password.min' => '密码长度不能少于5位',
            'password.max' => '密码长度不能超过50位',

            // 昵称
            'nickname.string' => '用户昵称必须是字符串',
            'nickname.max' => '用户昵称最多50个字符',

            // 头像
            'avatar.url' => '头像链接格式有误',

            // 手机号
            'mobile.regex' => '手机号格式不正确',

            // 邮箱
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过100个字符',

            // 性别
            'gender.in' => '性别值不合法',

            // 排序
            'sort.integer' => '排序必须是整数',
            'sort.min' => '排序不能小于0',

            // 备注
            'remark.max' => '备注最多500个字符',

            // 角色
            'role_ids.required' => '请选择角色',
            'role_ids.array' => '角色必须是数组格式',
            'role_ids.*.exists' => '选择的角色不存在',

            // 状态
            'status.required' => '请选择状态',
            'status.in' => '状态值不合法',

            // 修改密码
            'oldPassword.required' => '请输入旧密码',
            'newPassword.required' => '请输入新密码',
            'newPassword.min' => '新密码长度不能少于5位',
            'newPassword.max' => '新密码长度不能超过50位',
            'newPassword.confirmed' => '两次输入的新密码不一致',
            'newPassword.different' => '新密码不能与旧密码相同',
            'newPassword_confirmation.required' => '请确认新密码',
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