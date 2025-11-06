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

use Hyperf\Validation\ValidationExceptionHandler;

return [
    'handler' => [
        'http' => [
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            // JWT 异常处理器（放在前面，优先级高）
            App\Exception\Handler\JwtAuthExceptionHandler::class,
            // 业务异常处理器
            App\Exception\Handler\BusinessExceptionHandler::class,
            // 验证异常处理器
            App\Exception\Handler\ValidationExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];
