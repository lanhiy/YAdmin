<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\System\Logic\PermissionLogic;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Exceptions\JwtException;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use HyperfExtension\Jwt\Exceptions\TokenBlacklistedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Di\Annotation\Inject;

class JwtAuthMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    protected JwtFactoryInterface $jwtFactory;

    public function __construct(JwtFactoryInterface $jwtFactory)
    {
        $this->jwtFactory = $jwtFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $jwt = $this->jwtFactory->make();

            // 解析 Token
            $jwt->parseToken();


            // 验证 Token
            $payload = $jwt->checkOrFail();
            $adminId = $payload->get('admin_id');
            $username = $payload->get('username');

            // 将用户信息注入到请求中
            $request = $request
                ->withAttribute('jwt_payload', $payload)
                ->withAttribute('user_id', $payload->get('sub'))
                ->withAttribute('admin_id', $adminId)
                ->withAttribute('username', $username)
                ->withAttribute('nickname', $payload->get('nickname'));

            // 接口权限校验
            $this->checkPermission($request, (int) $adminId);

            return $handler->handle($request);

        } catch (TokenExpiredException) {
            throw new JwtAuthException(
                ErrorCode::TOKEN_EXPIRED,
                'Token 已过期，请重新登录',
                401
            );

        } catch (TokenBlacklistedException) {
            throw new JwtAuthException(
                ErrorCode::TOKEN_BLACKLISTED,
                'Token 已失效，请重新登录',
                401
            );

        } catch (JwtException $e) {
            throw new JwtAuthException(
                ErrorCode::TOKEN_INVALID,
                'Token 无效：' . $e->getMessage(),
                401
            );
        }
    }

    /**
     * 接口权限校验.
     *
     * 权限要求来自 system_route_permission 表；未登记的接口一律拒绝，
     * 确保新增接口不会因为漏配而处于无保护状态。
     */
    protected function checkPermission(ServerRequestInterface $request, int $adminId): void
    {
        $path = $request->getUri()->getPath();
        $route = $this->permissionLogic->resolveRoute($path, $request->getMethod());

        // 未登记的接口：拒绝访问，避免漏配导致接口失去保护
        if ($route === null) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                '该接口未配置访问权限，请联系管理员',
                403
            );
        }

        // 仅需登录即可访问
        if ($route['is_public']) {
            return;
        }

        // 超级管理员拥有全部权限
        if ($this->permissionLogic->isSuperAdmin($adminId)) {
            return;
        }

        $code = (string) $route['authority'];

        if ($code === '' || ! $this->permissionLogic->hasPermission($adminId, $code)) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                $code === '' ? '该接口未配置访问权限，请联系管理员' : "您没有权限执行此操作 [$code]",
                403
            );
        }
    }

}