<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\Model\SystemAdmin;
use App\Model\SystemAdminRole;
use Donjan\Casbin\Enforcer;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Exceptions\JwtException;
use HyperfExtension\Jwt\Exceptions\TokenExpiredException;
use HyperfExtension\Jwt\Exceptions\TokenBlacklistedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Context\Context;
use Hyperf\Redis\Redis;
use Hyperf\Di\Annotation\Inject;

class JwtAuthMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected Redis $redis;

    protected JwtFactoryInterface $jwtFactory;

    // 白名单路径（不需要权限检查的接口）
    protected array $whitelist = [
        '/system/user/info',
        '/system/user/logout',
        '/system/menu/routes',
        '/system/menu/list',
        '/system/profile',
    ];

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

            // ✅ 权限检查（只检查按钮权限）
            $this->checkPermission($request, $adminId);

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
     * 检查权限（只检查按钮权限 authority）
     */
    protected function checkPermission(ServerRequestInterface $request, int $adminId): void
    {
        $path = $request->getUri()->getPath();

        // 白名单路径，直接通过
        if ($this->isWhitelist($path)) {
            return;
        }

        // ✅ 从 Redis 缓存获取用户角色（减少数据库查询）
        $cacheKey = "user:roles:$adminId";
        $userRoles = $this->redis->get($cacheKey);

        if ($userRoles === null) {
            // 缓存不存在，从数据库查询
            $userRoles = $this->getUserRoles($adminId);
            // 缓存 30 分钟
            $this->redis->setex($cacheKey, 1800, json_encode($userRoles));
        } else {
            $userRoles = json_decode($userRoles, true);
        }

        // 超级管理员直接通过
        if ($userRoles['is_super'] ?? false) {
            return;
        }

        $roleCodes = $userRoles['roles'] ?? [];
        if (empty($roleCodes)) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                '您没有访问权限',
                403
            );
        }

        // ✅ 获取路由对应的 authority（从路由注解或配置中获取）
        $authority = $this->getRouteAuthority($path);

        // 如果路由没有配置 authority，说明不需要特殊权限，直接通过
        if (empty($authority)) {
            return;
        }

        // ✅ 检查用户角色是否有该 authority 权限
        $hasPermission = false;
        foreach ($roleCodes as $roleCode) {
            if (Enforcer::enforce($roleCode, $authority, '*')) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                "您没有权限执行此操作 [$authority]",
                403
            );
        }
    }

    /**
     * 从 Redis 获取用户角色信息
     */
    protected function getUserRoles(int $adminId): array
    {
        // 这里可以用你的 AdminLogic 或者直接查询
        $admin = SystemAdmin::query()
            ->select(['id', 'is_super'])
            ->find($adminId);

        if (!$admin) {
            return ['is_super' => false, 'roles' => []];
        }

//        if ($admin->is_super == 1) {
//            return ['is_super' => true, 'roles' => []];
//        }

        // 获取用户角色编码
        $roleCodes = SystemAdminRole::query()
            ->where('admin_id', $adminId)
            ->join('system_role', 'system_admin_role.role_id', '=', 'system_role.id')
            ->where('system_role.status', 1)
            ->pluck('system_role.code')
            ->toArray();

        return [
            'is_super' => false,
            'roles' => $roleCodes,
        ];
    }

    /**
     * 获取路由对应的 authority
     */
    protected function getRouteAuthority(string $path): ?string
    {
        // ✅ 路由与 authority 的映射配置
        $routeAuthorityMap = [
            // 用户管理
            '/system/admin'         => 'system:admin:add',
            '/system/admin/list'    => 'system:admin:list',

            // 角色管理
            '/system/role'          => 'system:role:add',
            '/system/role/list'     => 'system:role:list',

            // 菜单管理
            '/system/menu'          => 'system:menu:add',
            '/system/menu/list'     => null, // 不需要权限

            // 配置管理
            '/system/config/update' => 'system:config:edit',
            '/system/config/list'   => 'system:config:list',
        ];

        // 处理动态路由（如 /system/admin/123）
        foreach ($routeAuthorityMap as $pattern => $authority) {
            if (str_starts_with($path, $pattern)) {
                // 精确匹配
                if ($path === $pattern) {
                    return $authority;
                }

                // 动态路由匹配（如 PUT /system/admin/123）
                if (preg_match('#^' . $pattern . '/\d+$#', $path)) {
                    // 根据 HTTP 方法判断
                    $method = Context::get(ServerRequestInterface::class)->getMethod();
                    if ($method === 'PUT') {
                        return str_replace(':add', ':edit', $authority);
                    } elseif ($method === 'DELETE') {
                        return str_replace(':add', ':delete', $authority);
                    } elseif ($method === 'GET') {
                        return str_replace(':add', ':view', $authority);
                    }
                }
            }
        }

        return null;
    }

    /**
     * 判断是否在白名单中
     */
    protected function isWhitelist(string $path): bool
    {
        foreach ($this->whitelist as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }
        return false;
    }
}