<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Exceptions\JwtException;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use HyperfExtension\Jwt\Exceptions\TokenBlacklistedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
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
            try {
                $jwt->parseToken($request);
            } catch (JwtException $e) {
                // Token 缺失或解析失败
                throw new JwtAuthException(
                    ErrorCode::TOKEN_MISSING,
                    'Token 缺失，请先登录'
                );
            }

            // 验证 Token
            $payload = $jwt->checkOrFail();

            // 将用户信息注入到请求中
            $request = $request
                ->withAttribute('jwt_payload', $payload)
                ->withAttribute('user_id', $payload->get('sub'))
                ->withAttribute('admin_id', $payload->get('admin_id'))
                ->withAttribute('username', $payload->get('username'))
                ->withAttribute('nickname', $payload->get('nickname'));

            return $handler->handle($request);

        } catch (TokenExpiredException $e) {
            // Token 已过期
            throw new JwtAuthException(
                ErrorCode::TOKEN_EXPIRED,
                'Token 已过期，请重新登录'
            );

        } catch (TokenBlacklistedException $e) {
            // Token 在黑名单中（已登出）
            throw new JwtAuthException(
                ErrorCode::TOKEN_BLACKLISTED,
                'Token 已失效，请重新登录'
            );

        } catch (JwtAuthException $e) {
            // 重新抛出 JWT 认证异常
            throw $e;

        } catch (JwtException $e) {
            // 其他 JWT 相关异常（签名错误、格式错误等）
            throw new JwtAuthException(
                ErrorCode::TOKEN_INVALID,
                'Token 无效：' . $e->getMessage()
            );

        } catch (\Throwable $e) {
            // 其他未知异常
            throw new JwtAuthException(
                ErrorCode::TOKEN_PARSE_ERROR,
                'Token 解析失败'
            );
        }
    }
}