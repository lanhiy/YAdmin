<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\System\Logic\PermissionLogic;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Exceptions\JwtException;
use HyperfExtension\Jwt\Exceptions\TokenBlacklistedException;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 认证中间件：只负责「你是谁」.
 *
 * 「你能做什么」由 PermissionMiddleware 负责，两者职责分离。
 * 本中间件额外校验账号当前状态：JWT 是无状态的，被禁用或删除的账号
 * 若只看 token 会一直有效到过期（JWT_TTL 默认 6 小时），
 * 因此每个请求都要回查一次账号状态（走身份快照缓存，不额外压库）。
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    public function __construct(protected JwtFactoryInterface $jwtFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $jwt = $this->jwtFactory->make();
            $jwt->parseToken();
            $payload = $jwt->checkOrFail();
        } catch (TokenExpiredException) {
            throw new JwtAuthException(ErrorCode::TOKEN_EXPIRED, 'Token 已过期，请重新登录', 401);
        } catch (TokenBlacklistedException) {
            throw new JwtAuthException(ErrorCode::TOKEN_BLACKLISTED, 'Token 已失效，请重新登录', 401);
        } catch (JwtException $e) {
            throw new JwtAuthException(ErrorCode::TOKEN_INVALID, 'Token 无效：' . $e->getMessage(), 401);
        }

        $adminId = (int) $payload->get('admin_id');

        // 账号状态校验：token 有效不等于账号仍可用
        $identity = $this->permissionLogic->identity($adminId);

        if (! $identity->exists) {
            throw new JwtAuthException(ErrorCode::TOKEN_INVALID, '账号不存在，请重新登录', 401);
        }

        if (! $identity->enabled) {
            throw new JwtAuthException(ErrorCode::FORBIDDEN, '账号已被禁用，请联系管理员', 403);
        }

        $request = $request
            ->withAttribute('jwt_payload', $payload)
            ->withAttribute('user_id', $payload->get('sub'))
            ->withAttribute('admin_id', $adminId)
            ->withAttribute('username', $payload->get('username'))
            ->withAttribute('nickname', $payload->get('nickname'))
            // 供 PermissionMiddleware 复用，避免重复读取
            ->withAttribute('admin_identity', $identity);

        return $handler->handle($request);
    }
}
