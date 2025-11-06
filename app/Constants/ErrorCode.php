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

    case VALIDATE_FAILED=422;

    #[Message("请求成功")]
    case REQUEST_SUCCESS= 0;

    #[Message("请求失败")]
    case REQUEST_FAILED= 1;
}
