<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\System\Service\AdminSessionService;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Exceptions\JwtException;
use HyperfExtension\Jwt\Exceptions\TokenBlacklistedException;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 只负责验证 JWT 和登录会话，不在这里混入资源授权逻辑。
 * 授权由后续的 PermissionMiddleware 统一处理。
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    /**
     * @param JwtFactoryInterface $jwtFactory JWT 解析工厂
     * @param AdminSessionService $adminSessionService 管理员会话版本服务
     */
    public function __construct(
        private readonly JwtFactoryInterface $jwtFactory,
        private readonly AdminSessionService $adminSessionService,
    ) {
    }

    /**
     * 验证 JWT、登录会话并向请求属性写入管理员身份。
     *
     * @param ServerRequestInterface $request 当前 HTTP 请求
     * @param RequestHandlerInterface $handler 下游请求处理器
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $jwt = $this->jwtFactory->make();
            if (!$jwt->getRequestParser()->hasToken($request)) {
                throw new JwtAuthException(ErrorCode::TOKEN_MISSING, statusCode: 401);
            }

            $jwt->parseToken();
            $payload = $jwt->checkOrFail();
            $adminId = (int) $payload->get('admin_id');

            if (!$this->adminSessionService->isValid(
                $adminId,
                (int) ($payload->get('iat') ?? 0),
                (string) ($payload->get('auth_session') ?? ''),
            )) {
                throw new TokenBlacklistedException('登录会话已失效');
            }

            $request = $request
                ->withAttribute('jwt_payload', $payload)
                ->withAttribute('user_id', $payload->get('sub'))
                ->withAttribute('admin_id', $adminId)
                ->withAttribute('username', $payload->get('username'))
                ->withAttribute('nickname', $payload->get('nickname'));

            return $handler->handle($request);
        } catch (TokenExpiredException) {
            throw new JwtAuthException(ErrorCode::TOKEN_EXPIRED, 'Token 已过期，请重新登录', 401);
        } catch (TokenBlacklistedException) {
            throw new JwtAuthException(ErrorCode::TOKEN_BLACKLISTED, 'Token 已失效，请重新登录', 401);
        } catch (JwtException $e) {
            throw new JwtAuthException(ErrorCode::TOKEN_INVALID, 'Token 无效：' . $e->getMessage(), 401);
        }
    }
}
