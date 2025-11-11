<?php

declare(strict_types=1);

namespace App\System\Request;

use Hyperf\Validation\Request\FormRequest;

class SystemMenuRequest extends FormRequest
{
    protected array $scenes = [
        'save' => [
            'parent_id', 'name', 'path', 'component', 'redirect', 'type',
            'title', 'icon', 'active_icon', 'hide_in_menu', 'hide_in_tab',
            'hide_in_breadcrumb', 'hide_children_in_menu', 'keep_alive',
            'authority', 'ignore_access', 'menu_visible_with_forbidden',
            'badge', 'badge_type', 'badge_variants', 'affix_tab',
            'affix_tab_order', 'full_path_key', 'active_path',
            'max_num_of_open_tab', 'link', 'iframe_src', 'open_in_new_window',
            'no_basic_layout', 'query', 'sort', 'status', 'remark'
        ],
        'update' => [
            'parent_id', 'name', 'path', 'component', 'redirect', 'type',
            'title', 'icon', 'active_icon', 'hide_in_menu', 'hide_in_tab',
            'hide_in_breadcrumb', 'hide_children_in_menu', 'keep_alive',
            'authority', 'ignore_access', 'menu_visible_with_forbidden',
            'badge', 'badge_type', 'badge_variants', 'affix_tab',
            'affix_tab_order', 'full_path_key', 'active_path',
            'max_num_of_open_tab', 'link', 'iframe_src', 'open_in_new_window',
            'no_basic_layout', 'query', 'sort', 'status', 'remark'
        ],
        'changeStatus' => ['id', 'status'],
    ];

    /**
     * 验证规则
     */
    public function rules(): array
    {
        $scene = $this->getScene();

        return [
            'id' => $scene === 'changeStatus' ? 'required|integer' : 'sometimes|integer',
            'parent_id' => 'sometimes|nullable|integer',
            'name' => 'sometimes|required|string|max:100',
            'path' => 'sometimes|required|string|max:255',
            'component' => 'sometimes|nullable|string|max:255',
            'redirect' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|required|integer|in:1,2,3',
            'title' => 'sometimes|required|string|max:100',
            'icon' => 'sometimes|nullable|string|max:100',
            'active_icon' => 'sometimes|nullable|string|max:100',
            'hide_in_menu' => 'sometimes|boolean',
            'hide_in_tab' => 'sometimes|boolean',
            'hide_in_breadcrumb' => 'sometimes|boolean',
            'hide_children_in_menu' => 'sometimes|boolean',
            'keep_alive' => 'sometimes|boolean',
            'authority' => 'sometimes|nullable|array',
            'ignore_access' => 'sometimes|boolean',
            'menu_visible_with_forbidden' => 'sometimes|boolean',
            'badge' => 'sometimes|nullable|string|max:50',
            'badge_type' => 'sometimes|nullable|string|in:dot,normal',
            'badge_variants' => 'sometimes|nullable|string|in:default,destructive,primary,success,warning',
            'affix_tab' => 'sometimes|boolean',
            'affix_tab_order' => 'sometimes|nullable|integer',
            'full_path_key' => 'sometimes|boolean',
            'active_path' => 'sometimes|nullable|string|max:255',
            'max_num_of_open_tab' => 'sometimes|nullable|integer',
            'link' => 'sometimes|nullable|string|max:500',
            'iframe_src' => 'sometimes|nullable|string|max:500',
            'open_in_new_window' => 'sometimes|boolean',
            'no_basic_layout' => 'sometimes|boolean',
            'query' => 'sometimes|nullable|array',
            'sort' => 'sometimes|nullable|integer',
            'status' => $scene === 'changeStatus' ? 'required|integer|in:0,1' : 'sometimes|integer|in:0,1',
            'remark' => 'sometimes|nullable|string|max:500',
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