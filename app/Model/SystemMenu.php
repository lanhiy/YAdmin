<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id 菜单ID
 * @property int $parent_id 父菜单ID，0为顶级菜单
 * @property string $name 路由名称（英文）
 * @property string $path 路由路径
 * @property string $component 组件路径
 * @property string $redirect 重定向路径
 * @property int $type 菜单类型：1-目录，2-菜单，3-按钮
 * @property string $title 菜单标题（支持国际化key）
 * @property string $icon 菜单图标
 * @property string $active_icon 激活图标
 * @property int $hide_in_menu 是否在菜单中隐藏：0-否，1-是
 * @property int $hide_in_tab 是否在标签页中隐藏：0-否，1-是
 * @property int $hide_in_breadcrumb 是否在面包屑中隐藏：0-否，1-是
 * @property int $hide_children_in_menu 是否隐藏子菜单：0-否，1-是
 * @property int $keep_alive 是否缓存页面：0-否，1-是
 * @property string $authority 权限标识数组，如：["sys:user:view"]
 * @property int $ignore_access 是否忽略权限：0-否，1-是
 * @property int $menu_visible_with_forbidden 菜单可见但访问403：0-否，1-是
 * @property string $badge 徽标文本
 * @property string $badge_type 徽标类型：dot-小红点，normal-文本
 * @property string $badge_variants 徽标颜色：default/destructive/primary/success/warning
 * @property int $affix_tab 是否固定标签页：0-否，1-是
 * @property int $affix_tab_order 固定标签页排序
 * @property int $full_path_key 完整路径作为key：0-否，1-是
 * @property string $active_path 激活的菜单路径
 * @property int $max_num_of_open_tab 最大打开标签数，-1为不限制
 * @property string $link 外链地址
 * @property string $iframe_src iframe地址
 * @property int $open_in_new_window 是否新窗口打开：0-否，1-是
 * @property int $no_basic_layout 不使用基础布局：0-否，1-是
 * @property string $query 路由参数，JSON格式
 * @property int $sort 排序（升序）
 * @property int $status 状态：0-禁用，1-启用
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class SystemMenu extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_menu';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'parent_id', 'name', 'path', 'component', 'redirect', 'type', 'title', 'icon', 'active_icon', 'hide_in_menu', 'hide_in_tab', 'hide_in_breadcrumb', 'hide_children_in_menu', 'keep_alive', 'authority', 'ignore_access', 'menu_visible_with_forbidden', 'badge', 'badge_type', 'badge_variants', 'affix_tab', 'affix_tab_order', 'full_path_key', 'active_path', 'max_num_of_open_tab', 'link', 'iframe_src', 'open_in_new_window', 'no_basic_layout', 'query', 'sort', 'status', 'remark', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'type' => 'integer', 'hide_in_menu' => 'integer', 'hide_in_tab' => 'integer', 'hide_in_breadcrumb' => 'integer', 'hide_children_in_menu' => 'integer', 'keep_alive' => 'integer', 'ignore_access' => 'integer', 'menu_visible_with_forbidden' => 'integer', 'affix_tab' => 'integer', 'affix_tab_order' => 'integer', 'full_path_key' => 'integer', 'max_num_of_open_tab' => 'integer', 'open_in_new_window' => 'integer', 'no_basic_layout' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 菜单类型常量
     */
    public const TYPE_CATALOG = 1; // 目录
    public const TYPE_MENU = 2;    // 菜单
    public const TYPE_BUTTON = 3;  // 按钮

    /**
     * 状态常量
     */
    public const STATUS_DISABLED = 0; // 禁用
    public const STATUS_ENABLED = 1;  // 启用

    /**
     * 获取子菜单
     */
    public function children()
    {
        return $this->hasMany(SystemMenu::class, 'parent_id', 'id')
            ->where('status', self::STATUS_ENABLED)
            ->orderBy('sort', 'asc');
    }

    /**
     * 获取父菜单
     */
    public function parent()
    {
        return $this->belongsTo(SystemMenu::class, 'parent_id', 'id');
    }

    /**
     * 获取所有启用的菜单（树形结构）
     *
     * @return array
     */
    public static function getMenuTree(int $parentId = 0): array
    {
        $menus = self::where('parent_id', $parentId)
            ->where('status', self::STATUS_ENABLED)
            ->orderBy('sort', 'asc')
            ->get();

        $result = [];
        foreach ($menus as $menu) {
            $item = $menu->toArray();

            // 递归获取子菜单
            $children = self::getMenuTree($menu->id);
            if (!empty($children)) {
                $item['children'] = $children;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * 转换为前端路由格式
     *
     * @return array
     */
    public function toRoute(): array
    {
        $route = [
            'name' => $this->name,
            'path' => $this->path,
        ];

        // 组件路径
        if ($this->component) {
            $route['component'] = $this->component;
        }

        // 重定向
        if ($this->redirect) {
            $route['redirect'] = $this->redirect;
        }

        // Meta 配置
        $meta = [
            'title' => $this->title,
        ];

        // 图标
        if ($this->icon) {
            $meta['icon'] = $this->icon;
        }
        if ($this->active_icon) {
            $meta['activeIcon'] = $this->active_icon;
        }

        // 显示控制
        if ($this->hide_in_menu) {
            $meta['hideInMenu'] = true;
        }
        if ($this->hide_in_tab) {
            $meta['hideInTab'] = true;
        }
        if ($this->hide_in_breadcrumb) {
            $meta['hideInBreadcrumb'] = true;
        }
        if ($this->hide_children_in_menu) {
            $meta['hideChildrenInMenu'] = true;
        }

        // 缓存和权限
        if ($this->keep_alive) {
            $meta['keepAlive'] = true;
        }
        if ($this->authority) {
            $meta['authority'] = $this->authority;
        }
        if ($this->ignore_access) {
            $meta['ignoreAccess'] = true;
        }
        if ($this->menu_visible_with_forbidden) {
            $meta['menuVisibleWithForbidden'] = true;
        }

        // 徽标
        if ($this->badge) {
            $meta['badge'] = $this->badge;
            $meta['badgeType'] = $this->badge_type;
            $meta['badgeVariants'] = $this->badge_variants;
        }

        // 标签页
        if ($this->affix_tab) {
            $meta['affixTab'] = true;
            $meta['affixTabOrder'] = $this->affix_tab_order;
        }
        if (!$this->full_path_key) {
            $meta['fullPathKey'] = false;
        }
        if ($this->active_path) {
            $meta['activePath'] = $this->active_path;
        }
        if ($this->max_num_of_open_tab > 0) {
            $meta['maxNumOfOpenTab'] = $this->max_num_of_open_tab;
        }

        // 外链和iframe
        if ($this->link) {
            $meta['link'] = $this->link;
        }
        if ($this->iframe_src) {
            $meta['iframeSrc'] = $this->iframe_src;
        }
        if ($this->open_in_new_window) {
            $meta['openInNewWindow'] = true;
        }

        // 其他配置
        if ($this->no_basic_layout) {
            $meta['noBasicLayout'] = true;
        }
        if ($this->query) {
            $meta['query'] = $this->query;
        }
        if ($this->sort !== 0) {
            $meta['order'] = $this->sort;
        }

        $route['meta'] = $meta;

        return $route;
    }

    /**
     * 获取完整的前端路由树
     *
     * @return array
     */
    public static function getRouteTree(int $parentId = 0): array
    {
        $menus = self::where('parent_id', $parentId)
            ->where('status', self::STATUS_ENABLED)
            ->where('type', '!=', self::TYPE_BUTTON) // 按钮不作为路由
            ->orderBy('sort', 'asc')
            ->get();

        $routes = [];
        foreach ($menus as $menu) {
            $route = $menu->toRoute();

            // 递归获取子路由
            $children = self::getRouteTree($menu->id);
            if (!empty($children)) {
                $route['children'] = $children;
            }

            $routes[] = $route;
        }

        return $routes;
    }
}
