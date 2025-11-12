<?php

declare(strict_types=1);

namespace App\Utils;

use App\Constants\ErrorCode;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class JsonUtils
{
    protected ResponseInterface $response;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->response = $container->get(ResponseInterface::class);
    }

    /**
     * 成功响应
     * @param mixed $data 返回数据
     * @param string|null $message 提示信息（为 null 时使用 ErrorCode 默认消息）
     * @return PsrResponseInterface
     */
    public function success(mixed $data = null, ?string $message = null): PsrResponseInterface
    {
        // 判断是否为空数组、空对象、空字符串
        if ($this->isEmpty($data)) {
            $data = null;
        }

        return $this->response->json([
            'code' => ErrorCode::REQUEST_SUCCESS->value,  // ✅ 使用 ->value
            'message' => $message ?? ErrorCode::REQUEST_SUCCESS->getMessage(),  // ✅ 使用实际文本
            'data' => $data
        ]);
    }

    /**
     * 失败响应 - 使用 ErrorCode
     * @param ErrorCode $errorCode 错误码枚举
     * @param string|null $customMessage 自定义消息（为 null 时使用 ErrorCode 默认消息）
     * @param mixed $data 返回数据
     * @return PsrResponseInterface
     */
    public function fail(ErrorCode $errorCode, ?string $customMessage = null, mixed $data = null): PsrResponseInterface
    {
        return $this->response->json([
            'code' => $errorCode->value,
            'message' => $customMessage ?? $errorCode->getMessage(),
            'data' => $data
        ]);
    }

    /**
     * 自定义响应
     * @param int $code 状态码
     * @param string $message 提示信息
     * @param mixed $data 返回数据
     * @return PsrResponseInterface
     */
    public function custom(int $code, string $message, mixed $data = null): PsrResponseInterface
    {
        return $this->response->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * 判断数据是否为空
     * @param mixed $data
     * @return bool
     */
    private function isEmpty(mixed $data): bool
    {
        // 空字符串
        if ($data === '') {
            return true;
        }

        // 空数组
        if (is_array($data) && count($data) === 0) {
            return true;
        }

        // 空对象(stdClass 或其他对象)
        if (is_object($data)) {
            // 将对象转换为数组检查
            $arr = (array)$data;
            if (count($arr) === 0) {
                return true;
            }
        }

        return false;
    }
}