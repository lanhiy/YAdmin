<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum ErrorCode: int
{
    use EnumConstantsTrait;

    #[Message("服务异常")]
    case SERVER_ERROR = 500;

    case VALIDATE_FAILED = 422;

    #[Message("请求成功")]
    case REQUEST_SUCCESS = 0;

    #[Message("请求失败")]
    case REQUEST_FAILED = 1;

    // JWT 相关错误码
    #[Message("未授权，请先登录")]
    case UNAUTHORIZED = 401;

    #[Message("Token 缺失")]
    case TOKEN_MISSING = 40100;

    #[Message("Token 已过期")]
    case TOKEN_EXPIRED = 40101;

    #[Message("Token 无效")]
    case TOKEN_INVALID = 40102;

    #[Message("Token 已失效，请重新登录")]
    case TOKEN_BLACKLISTED = 40103;

    #[Message("Token 解析失败")]
    case TOKEN_PARSE_ERROR = 40104;
}