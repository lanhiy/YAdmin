<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Constants\ErrorCode;
use Hyperf\Codec\Json;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected StdoutLoggerInterface $logger,
        protected ConfigInterface $config  // ✅ 注入配置
    ) {}

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->logger->error(sprintf(
            '[%s] %s in %s:%d',
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine()
        ), [
            'trace' => $throwable->getTraceAsString(),
        ]);

        // ✅ 使用注入的 config
        $appEnv = $this->config->get('app_env', 'dev');

        // 生产环境使用 ErrorCode 的消息，开发环境显示实际错误
        $message = $appEnv === 'prod'
            ? ErrorCode::SERVER_ERROR->getMessage()
            : $throwable->getMessage();

        $format = [
            'code' => ErrorCode::SERVER_ERROR->value,
            'message' => $message,
            'data' => null,
        ];

        // 开发环境返回调试信息
        if ($appEnv !== 'prod') {
            $format['debug'] = [
                'exception' => get_class($throwable),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ];
        }

        return $response
            ->withHeader('Server', 'Hyperf')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'accept-language,authorization,lang,uid,token,Keep-Alive,User-Agent,Cache-Control,Content-Type')
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withStatus(500)
            ->withBody(new SwooleStream(Json::encode($format)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}