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
