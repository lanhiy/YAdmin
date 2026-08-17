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
use App\Factory\JwtRequestParserFactory;
use HyperfExtension\Jwt\Contracts\RequestParser\RequestParserInterface;

return [
    // JWT 仅从 Authorization Bearer Header 读取，兼容 Hyperf 3.2 的 PSR Request。
    RequestParserInterface::class => JwtRequestParserFactory::class,
];
