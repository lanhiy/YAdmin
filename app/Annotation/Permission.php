<?php

declare(strict_types=1);

namespace App\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 接口权限声明.
 *
 * 权限码与 system_menu.authority 使用同一契约：菜单按钮用于前端可见性，
 * 注解用于后端接口校验，角色只授权菜单/按钮节点。
 *
 * 用法：
 *   #[Permission('system:role:list', '查看列表')]
 *   #[Permission(['system:role:add', 'system:role:edit'], '保存')]       // 任一即可
 *   #[Permission(['a', 'b'], mode: Permission::MODE_ALL)]                // 需全部满足
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Permission extends AbstractAnnotation
{
    /**
     * 满足任意一个权限即可通过.
     */
    public const MODE_ANY = 'any';

    /**
     * 必须满足全部权限才能通过.
     */
    public const MODE_ALL = 'all';

    /**
     * 所需权限标识列表.
     *
     * @var string[]
     */
    public array $codes = [];

    /**
     * @param array<int, string>|string $code 权限标识，如 system:role:list
     * @param string $name 权限显示名，用于权限树叶子节点
     * @param string $mode 多个权限的匹配模式：any-任一，all-全部
     * @param int $sort 同分组内排序，升序
     */
    public function __construct(
        array|string $code,
        public string $name = '',
        public string $mode = self::MODE_ANY,
        public int $sort = 0,
    ) {
        $codes = is_string($code) ? [$code] : $code;

        // 过滤空值并去重：空权限码若被当作「无需权限」处理会造成意外放行，
        // 这里直接剔除，由中间件的 fail-closed 逻辑拒绝无有效声明的接口。
        $this->codes = array_values(array_unique(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $codes),
            static fn (string $item): bool => $item !== '',
        )));

        $this->mode = $mode === self::MODE_ALL ? self::MODE_ALL : self::MODE_ANY;
    }

    /**
     * 权限声明是否有效（含至少一个权限码）.
     */
    public function isValid(): bool
    {
        return $this->codes !== [];
    }

    /**
     * 推导分组标识：取权限码去掉最后一段，如 system:role:list => system:role.
     */
    public function resolveModule(string $code): string
    {
        $segments = explode(':', $code);

        if (count($segments) <= 1) {
            return $code;
        }

        array_pop($segments);

        return implode(':', $segments);
    }
}
