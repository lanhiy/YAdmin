<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            // ⚠️ 注意：异常处理器的顺序很重要！从上到下依次匹配

            // 1️⃣ HTTP 异常（404, 405 等）- 最先处理
            App\Exception\Handler\HttpExceptionHandler::class,

            // 2️⃣ JWT 认证异常
            App\Exception\Handler\JwtAuthExceptionHandler::class,

            // 3️⃣ 验证异常
            App\Exception\Handler\ValidationExceptionHandler::class,

            // 4️⃣ 业务异常
            App\Exception\Handler\BusinessExceptionHandler::class,

            // 5️⃣ 默认异常处理器（捕获所有未处理的异常）- 放在最后
            App\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];