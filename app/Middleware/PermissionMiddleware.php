<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\System\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 认证后的资源授权层。
 *
 * 中间件从数据表解析接口策略，并以默认拒绝方式阻止未登记的新接口。
 */
class PermissionMiddleware implements MiddlewareInterface
{
    /** @param PermissionService $permissions 数据表权限服务 */
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    /**
     * 校验请求对应的 API 策略与当前管理员权限。
     *
     * @param ServerRequestInterface $request 已经过 JWT 身份认证的请求
     * @param RequestHandlerInterface $handler 下游请求处理器
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // CORS 预检没有 JWT，也不应触发业务权限判断。
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $handler->handle($request);
        }

        $adminId = (int) $request->getAttribute('admin_id', 0);
        if ($adminId <= 0) {
            throw new JwtAuthException(ErrorCode::UNAUTHORIZED, '登录状态无效', 401);
        }

        $resolved = $this->permissions->resolve($request->getMethod(), $request->getUri()->getPath());
        if (!$resolved['found']) {
            throw new JwtAuthException(ErrorCode::FORBIDDEN, '接口未配置权限策略', 403);
        }

        if ($resolved['ignore_access']) {
            return $handler->handle($request);
        }

        $required = $resolved['permissions'];
        if ($required === []) {
            throw new JwtAuthException(ErrorCode::FORBIDDEN, '接口权限策略缺少权限标识', 403);
        }
        if (!$this->permissions->canAny($adminId, $required)) {
            throw new JwtAuthException(ErrorCode::FORBIDDEN, '您没有权限执行此操作 [' . implode('|', $required) . ']', 403);
        }

        return $handler->handle($request);
    }
}
