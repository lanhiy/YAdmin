<?php

declare(strict_types=1);

namespace App\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 控制器级权限分组声明.
 *
 * 只提供分组显示名，分组标识（module）由权限码自身推导，
 * 因此同一控制器里跨模块的权限码也能各归其位。
 *
 * 用法：#[PermissionGroup('角色管理')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PermissionGroup extends AbstractAnnotation
{
    /**
     * @param string $name 分组显示名，用于权限树分组标题
     * @param int $sort 分组排序，升序
     */
    public function __construct(
        public string $name = '',
        public int $sort = 0,
    ) {
    }
}
