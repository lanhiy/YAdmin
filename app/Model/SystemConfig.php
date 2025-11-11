<?php

declare(strict_types=1);

namespace App\Model;



/**
 * @property int $id ID
 * @property string $config_key 配置键名
 * @property string $config_value 配置值(JSON格式)
 * @property string $config_type 配置类型：app,logo,theme,copyright,layout,tabbar,sidebar,header,breadcrumb,footer
 * @property string $description 配置描述
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class SystemConfig extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'system_config';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'config_key', 'config_value', 'config_type', 'description', 'sort', 'status', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];


    /**
     * 状态常量
     */
    public const STATUS_DISABLED = 0; // 禁用
    public const STATUS_ENABLED = 1;  // 启用

    /**
     * 配置类型常量
     */
    public const TYPE_APP = 'app';
    public const TYPE_LOGO = 'logo';
    public const TYPE_THEME = 'theme';
    public const TYPE_COPYRIGHT = 'copyright';
    public const TYPE_LAYOUT = 'layout';
    public const TYPE_TABBAR = 'tabbar';
    public const TYPE_SIDEBAR = 'sidebar';
    public const TYPE_HEADER = 'header';
    public const TYPE_BREADCRUMB = 'breadcrumb';
    public const TYPE_FOOTER = 'footer';
}
