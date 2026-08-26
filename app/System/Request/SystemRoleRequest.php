<?php

declare(strict_types=1);

namespace App\System\Request;

use Hyperf\Validation\Request\FormRequest;

class SystemRoleRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['name', 'code', 'description', 'sort', 'status', 'menu_ids'],
        'update' => ['name', 'code', 'description', 'sort', 'status', 'menu_ids'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        return [
            'id' => 'sometimes|integer',
            'name' => 'required|string|max:50',
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/'],
            'description' => 'sometimes|nullable|string|max:255',
            'sort' => 'sometimes|integer|min:0',
            'status' => 'required|integer|in:0,1',
            'menu_ids' => 'sometimes|array',
            'menu_ids.*' => 'integer|min:1',
        ];
    }

    /**
     * 字段映射名称
     */
    public function attributes(): array
    {
        return [
            'id' => '角色ID',
            'name' => '角色名称',
            'code' => '角色编码',
            'description' => '角色描述',
            'sort' => '排序',
            'status' => '状态',
            'menu_ids' => '菜单权限',
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'name.required' => '请输入角色名称',
            'name.max' => '角色名称最多50个字符',
            'code.required' => '请输入角色编码',
            'code.max' => '角色编码最多50个字符',
            'description.max' => '角色描述最多255个字符',
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
