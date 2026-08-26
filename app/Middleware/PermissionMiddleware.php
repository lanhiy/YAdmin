<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Annotation\Permission;
use App\Annotation\WithoutPermission;
use App\Constants\ErrorCode;
use App\Exception\JwtAuthException;
use App\System\Logic\AdminIdentity;
use App\System\Logic\PermissionLogic;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 鉴权中间件：只负责「你能做什么」.
 *
 * 权限要求从控制器方法上的 #[Permission] 注解读取。相比旧的
 * 「路由表 + 路径正则归一化」方案，这里拿到的是路由已解析出的
 * [控制器, 方法]，不需要猜测 /system/role/12 里哪一段是参数，
 * 也不存在数据库映射与实际路由漂移的可能。
 *
 * 判定顺序：
 *   1. 无任何权限注解        -> 403（fail-closed，漏声明在联调即暴露）
 *   2. WithoutPermission     -> 放行（显式声明的公开接口）
 *   3. 超管                  -> 放行（ID=1 或持有 is_super 角色）
 *   4. 比对权限码            -> 按 any/all 模式判定
 */
class PermissionMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected PermissionLogic $permissionLogic;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handlerInfo = $this->resolveHandler($request);

        // 路由未匹配到控制器方法（如闭包路由）交给后续流程处理
        if ($handlerInfo === null) {
            return $handler->handle($request);
        }

        [$class, $method] = $handlerInfo;

        $annotations = AnnotationCollector::getClassMethodAnnotation($class, $method) ?? [];

        // 显式声明的公开接口：仅需登录
        if (isset($annotations[WithoutPermission::class])) {
            return $handler->handle($request);
        }

        $permission = $annotations[Permission::class] ?? null;

        // fail-closed：未声明权限的接口一律拒绝，避免新增接口漏配而失去保护
        if (! $permission instanceof Permission || ! $permission->isValid()) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                '该接口未声明访问权限，请联系管理员',
                403,
            );
        }

        $identity = $this->resolveIdentity($request);

        // 超管放行：ID=1 或持有 is_super 角色
        if ($identity->isSuper) {
            return $handler->handle($request);
        }

        $passed = $permission->mode === Permission::MODE_ALL
            ? $identity->canAll($permission->codes)
            : $identity->canAny($permission->codes);

        if (! $passed) {
            throw new JwtAuthException(
                ErrorCode::FORBIDDEN,
                '您没有权限执行此操作 [' . implode(', ', $permission->codes) . ']',
                403,
            );
        }

        return $handler->handle($request);
    }

    /**
     * 从已解析的路由中取出控制器类与方法.
     *
     * @return null|array{0: string, 1: string}
     */
    private function resolveHandler(ServerRequestInterface $request): ?array
    {
        $dispatched = $request->getAttribute(Dispatched::class);

        if (! $dispatched instanceof Dispatched || ! $dispatched->isFound()) {
            return null;
        }

        $callback = $dispatched->handler->callback ?? null;

        // 数组形式 [Controller::class, 'method']
        if (is_array($callback) && count($callback) === 2 && is_string($callback[0]) && is_string($callback[1])) {
            return [$callback[0], $callback[1]];
        }

        // 字符串形式 Controller@method 或 Controller::method
        if (is_string($callback)) {
            foreach (['@', '::'] as $separator) {
                if (str_contains($callback, $separator)) {
                    [$class, $method] = explode($separator, $callback, 2);

                    return [$class, $method];
                }
            }
        }

        return null;
    }

    /**
     * 取身份快照：优先复用认证中间件的结果.
     */
    private function resolveIdentity(ServerRequestInterface $request): AdminIdentity
    {
        $identity = $request->getAttribute('admin_identity');

        if ($identity instanceof AdminIdentity) {
            return $identity;
        }

        return $this->permissionLogic->identity((int) $request->getAttribute('admin_id'));
    }
}
