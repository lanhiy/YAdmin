<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;

/**
 * 系统菜单、按钮权限标识及 HTTP 接口策略模型。
 *
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
 * @property null|array<int, string> $authority 权限标识数组，如：["sys:user:view"]
 * @property null|array<int, array{method: string, path: string}> $api_routes API 路由策略数组
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
 * @property null|array<array-key, mixed> $query 路由参数，JSON格式
 * @property int $sort 排序（升序）
 * @property int $status 状态：0-禁用，1-启用
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class SystemMenu extends Model
{
    /** 菜单资源数据表。 */
    protected ?string $table = 'system_menu';

    /** @var array<int, string> 可批量写入的菜单字段 */
    protected array $fillable = ['id', 'parent_id', 'name', 'path', 'component', 'redirect', 'type', 'title', 'icon', 'active_icon', 'hide_in_menu', 'hide_in_tab', 'hide_in_breadcrumb', 'hide_children_in_menu', 'keep_alive', 'authority', 'api_routes', 'ignore_access', 'menu_visible_with_forbidden', 'badge', 'badge_type', 'badge_variants', 'affix_tab', 'affix_tab_order', 'full_path_key', 'active_path', 'max_num_of_open_tab', 'link', 'iframe_src', 'open_in_new_window', 'no_basic_layout', 'query', 'sort', 'status', 'remark', 'created_at', 'updated_at'];

    /** @var array<string, string> 数据库字段类型转换规则 */
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'type' => 'integer', 'hide_in_menu' => 'integer', 'hide_in_tab' => 'integer', 'hide_in_breadcrumb' => 'integer', 'hide_children_in_menu' => 'integer', 'keep_alive' => 'integer', 'authority' => 'array', 'api_routes' => 'array', 'ignore_access' => 'integer', 'menu_visible_with_forbidden' => 'integer', 'badge_variants' => 'string', 'affix_tab' => 'integer', 'affix_tab_order' => 'integer', 'full_path_key' => 'integer', 'max_num_of_open_tab' => 'integer', 'open_in_new_window' => 'integer', 'no_basic_layout' => 'integer', 'query' => 'array', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /** 目录资源类型。 */
    public const int TYPE_CATALOG = 1;

    /** 页面菜单资源类型。 */
    public const int TYPE_MENU = 2;

    /** 操作按钮资源类型。 */
    public const int TYPE_BUTTON = 3;

    /** 资源禁用状态。 */
    public const int STATUS_DISABLED = 0;

    /** 资源启用状态。 */
    public const int STATUS_ENABLED = 1;

    /**
     * 获取子菜单
     *
     * @return HasMany<SystemMenu, static>
     */
    public function children(): HasMany
    {
        $relation = $this->hasMany(SystemMenu::class, 'parent_id', 'id');
        $relation->where('status', self::STATUS_ENABLED)->orderBy('sort', 'asc');
        return $relation;
    }

    /**
     * 获取父菜单
     *
     * @return BelongsTo<SystemMenu, static>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SystemMenu::class, 'parent_id', 'id');
    }
}
