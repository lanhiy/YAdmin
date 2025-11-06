<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\Database\Commands\ModelOption;
use function Hyperf\Support\env;

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', 'hyperf'),
        'port' => env('DB_PORT', 3306),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8'),
        'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
                'uses' => '',
                'refresh_fillable' => true,
                'table_mapping' => [],
                'with_comments' => true,
                'property_case' => ModelOption::PROPERTY_SNAKE_CASE,
                'visitors' => [
                    //此 Visitor 可以根据数据库中主键，生成对应的 $incrementing $primaryKey 和 $keyType。
                    Hyperf\Database\Commands\Ast\ModelRewriteKeyInfoVisitor::class,
                    //此 Visitor 可以根据 DELETED_AT 常量判断该模型是否含有软删除字段，如果存在，则添加对应的 Trait SoftDeletes。
//                    Hyperf\Database\Commands\Ast\ModelRewriteSoftDeletesVisitor::class,
                    //此 Visitor 可以根据 created_at 和 updated_at 自动判断，是否启用默认记录 创建和修改时间 的功能。
                    Hyperf\Database\Commands\Ast\ModelRewriteTimestampsVisitor::class,
                    //此 Visitor 可以根据数据库字段生成对应的 getter 和 setter。
//                    Hyperf\Database\Commands\Ast\ModelRewriteGetterSetterVisitor::class,
                ],
            ],
        ],
    ],
];
