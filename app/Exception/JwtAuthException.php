<?php

declare(strict_types=1);

namespace App\Exception;

use App\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

class JwtAuthException extends ServerException
{
    public function __construct(
        ErrorCode $errorCode,
        string $message = '',
        int $statusCode = 401,
        ?Throwable $previous = null
    ) {
        if ($message === '') {
            $message = $errorCode->getMessage();
        }

        parent::__construct($message, $errorCode->value, $previous);
        $this->statusCode = $statusCode;
    }

    protected int $statusCode = 401;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
