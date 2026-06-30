<?php
/*
|--------------------------------------------------------------------------
| Common function method
|--------------------------------------------------------------------------
*/

use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 容器实例
 *
 * @return ContainerInterface
 */
function di(): ContainerInterface
{
    return ApplicationContext::getContainer();
}

if (!function_exists('generate_code')) {
    /**
     * 生成防混淆随机短码（去除易混淆的 0 1 I O l）
     *
     * @param int $length 码长度，默认 8 位
     * @return string
     */
    function generate_code(int $length = 8): string
    {
        $charset = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $max = strlen($charset) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, $max)];
        }
        return $code;
    }
}

if (! function_exists('get_client_ip')) {
    /**
     * 获取客户端 ip
     *
     * @return string
     */
    function get_client_ip(): string
    {
        try {
            $request =di()->get(Hyperf\HttpServer\Contract\RequestInterface::class);
            return $request->getHeaderLine('X-Forwarded-For')
                ?: $request->getHeaderLine('X-Real-IP')
                    ?: ($request->getServerParams()['remote_addr'] ?? '')
                        ?: '127.0.0.1';
        }catch (Throwable){
            return '127.0.0.1';
        }
    }
}
