<?php

declare(strict_types=1);

namespace App\Utils;

use App\Constants\ErrorCode;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class JsonUtils
{
    protected ResponseInterface $response;

    public function __construct(ContainerInterface $container)
    {
        $this->response = $container->get(ResponseInterface::class);
    }

    /**
     * 成功响应
     * @param mixed|array $data 返回数据
     * @param string $message 提示信息
     * @return PsrResponseInterface
     */
    public function success(mixed $data = null, string $message = '操作成功'): PsrResponseInterface
    {
        // 判断是否为空数组、空对象、空字符串
        if ($this->isEmpty($data)) {
            $data = null;
        }

        return $this->response->json([
            'code' => ErrorCode::REQUEST_SUCCESS,
            'message' => $message ?: ErrorCode::getMessage(ErrorCode::REQUEST_SUCCESS),
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

        // 空对象（stdClass 或其他对象）
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