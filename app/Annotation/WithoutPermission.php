<?php

declare(strict_types=1);

namespace App\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 显式声明接口无需权限校验（仅需通过登录认证）.
 *
 * 与 #[Permission(login: true)] 等价，独立成注解是为了让「有意公开」
 * 在代码里一眼可辨，而不是与「忘记声明权限」混为一谈。
 *
 * 未标注任何权限注解的接口会被中间件直接拒绝（fail-closed），
 * 因此新增接口漏配权限会在联调阶段暴露，而不是静默放开。
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class WithoutPermission extends AbstractAnnotation
{
    /**
     * @param string $reason 公开原因，仅作代码可读性说明
     */
    public function __construct(public string $reason = '')
    {
    }
}
