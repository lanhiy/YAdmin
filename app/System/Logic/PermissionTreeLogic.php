<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Model\SystemMenu;
use Hyperf\Di\Annotation\Inject;

/**
 * 菜单/按钮授权树。
 *
 * 保留旧接口路径只是为了兼容已有前端调用，数据源已经改为
 * system_menu.authority，不再读取独立权限表。
 */
class PermissionTreeLogic
{
    #[Inject]
    protected MenuLogic $menuLogic;

    /** @return array<int, array<string, mixed>> */
    public function getTree(): array
    {
        return $this->mapTree($this->menuLogic->getMenuTree());
    }

    /** @return array<int, array<string, mixed>> */
    public function getList(array $params = []): array
    {
        $keyword = trim((string) ($params['keyword'] ?? ''));
        $result = [];
        $this->flatten($this->menuLogic->getMenuTree(), $result, $keyword);

        return $result;
    }

    /** @param array<int, array<string, mixed>> $nodes @return array<int, array<string, mixed>> */
    private function mapTree(array $nodes): array
    {
        return array_map(function (array $node): array {
            $authority = array_values(array_filter((array) ($node['authority'] ?? []), 'is_string'));

            return [
                'id' => (int) ($node['id'] ?? 0),
                'code' => $authority[0] ?? '',
                'title' => (string) ($node['title'] ?? ''),
                'is_group' => (int) ($node['type'] ?? 0) !== SystemMenu::TYPE_BUTTON,
                'type' => (int) ($node['type'] ?? 0),
                'authority' => $authority,
                'children' => $this->mapTree((array) ($node['children'] ?? [])),
            ];
        }, $nodes);
    }

    /** @param array<int, array<string, mixed>> $nodes @param array<int, array<string, mixed>> $result */
    private function flatten(array $nodes, array &$result, string $keyword): void
    {
        foreach ($nodes as $node) {
            foreach ((array) ($node['authority'] ?? []) as $code) {
                $code = (string) $code;
                if ($keyword !== '' && ! str_contains($code, $keyword) && ! str_contains((string) $node['title'], $keyword)) {
                    continue;
                }
                $result[] = [
                    'id' => (int) $node['id'],
                    'code' => $code,
                    'name' => (string) $node['title'],
                    'module' => (string) ($node['name'] ?? ''),
                    'type' => (int) ($node['type'] ?? 0),
                    'is_synced' => true,
                ];
            }
            $this->flatten((array) ($node['children'] ?? []), $result, $keyword);
        }
    }
}
