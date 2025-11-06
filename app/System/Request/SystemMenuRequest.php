<?php

declare(strict_types=1);

namespace App\System\Request;

use Hyperf\Validation\Request\FormRequest;

class SystemMenuRequest extends FormRequest
{
    protected array $scenes = [
        'save' => ['name', 'path', 'type', 'title', 'parent_id'],
        'update' => ['name', 'path', 'type', 'title'],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'parent_id' => 'integer',
            'name' => 'required|string|max:100',
            'path' => 'required|string|max:255',
            'component' => 'string|max:255',
            'redirect' => 'string|max:255',
            'type' => 'required|integer|in:1,2,3',
            'title' => 'required|string|max:100',
            'icon' => 'string|max:100',
            'active_icon' => 'string|max:100',
            'hide_in_menu' => 'boolean',
            'hide_in_tab' => 'boolean',
            'hide_in_breadcrumb' => 'boolean',
            'hide_children_in_menu' => 'boolean',
            'keep_alive' => 'boolean',
            'authority' => 'array',
            'ignore_access' => 'boolean',
            'menu_visible_with_forbidden' => 'boolean',
            'badge' => 'string|max:50',
            'badge_type' => 'string|in:dot,normal',
            'badge_variants' => 'string|in:default,destructive,primary,success,warning',
            'affix_tab' => 'boolean',
            'affix_tab_order' => 'integer',
            'full_path_key' => 'boolean',
            'active_path' => 'string|max:255',
            'max_num_of_open_tab' => 'integer',
            'link' => 'string|max:500',
            'iframe_src' => 'string|max:500',
            'open_in_new_window' => 'boolean',
            'no_basic_layout' => 'boolean',
            'query' => 'array',
            'sort' => 'integer',
            'status' => 'required|integer|in:0,1',
            'remark' => 'string|max:500',
        ];
    }

    /**
     * 字段映射名称
     */
    public function attributes(): array
    {
        return [
            'id' => '菜单ID',
            'parent_id' => '父菜单',
            'name' => '路由名称',
            'path' => '路由路径',
            'component' => '组件路径',
            'redirect' => '重定向路径',
            'type' => '菜单类型',
            'title' => '菜单标题',
            'icon' => '菜单图标',
            'active_icon' => '激活图标',
            'status' => '状态',
            'sort' => '排序',
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'name.required' => '请输入路由名称',
            'name.max' => '路由名称最多100个字符',
            'path.required' => '请输入路由路径',
            'path.max' => '路由路径最多255个字符',
            'type.required' => '请选择菜单类型',
            'type.in' => '菜单类型值不合法',
            'title.required' => '请输入菜单标题',
            'title.max' => '菜单标题最多100个字符',
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