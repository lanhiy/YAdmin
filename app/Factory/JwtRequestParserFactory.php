<?php

declare(strict_types=1);

namespace App\Factory;

use HyperfExtension\Jwt\Contracts\RequestParser\RequestParserInterface;
use HyperfExtension\Jwt\RequestParser\Handlers\AuthHeaders;
use HyperfExtension\Jwt\RequestParser\RequestParser;

/** 创建只接受标准 Authorization Bearer Header 的 JWT 请求解析器。 */
class JwtRequestParserFactory
{
    /**
     * 构建应用使用的 JWT 请求解析器。
     *
     * 禁止从 URL、请求体和 Cookie 读取 Token，避免凭据出现在日志或业务参数中。
     */
    public function __invoke(): RequestParserInterface
    {
        return new RequestParser([new AuthHeaders()]);
    }
}
