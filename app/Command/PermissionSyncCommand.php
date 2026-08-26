<?php

declare(strict_types=1);

namespace App\Command;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Model\SystemPermission;
use App\System\Logic\PermissionLogic;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Symfony\Component\Console\Input\InputOption;

/**
 * 把代码中的 #[Permission] 注解同步到 system_permission 表.
 *
 * 权限的真相在代码里，数据库只是投影。这条命令保证两者一致：
 *   - 代码新增的权限     -> 入库
 *   - 代码变更的元数据   -> 更新（名称、分组、路由）
 *   - 代码已移除的权限   -> 标记 is_synced=0，不硬删
 *
 * 不硬删是有意的：直接删除会级联撤销角色授权且无从追溯，
 * 标记后由管理员在后台确认再清理（--prune 可强制清理）。
 */
#[Command]
class PermissionSyncCommand extends HyperfCommand
{
    #[Inject]
    protected DispatcherFactory $dispatcherFactory;

    #[Inject]
    protected PermissionLogic $permissionLogic;

    public function __construct()
    {
        parent::__construct('permission:sync');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('扫描控制器 #[Permission] 注解并同步权限点表');
        $this->addOption('prune', null, InputOption::VALUE_NONE, '物理删除代码中已不存在的权限点（同时撤销相关角色授权）');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, '只输出将要发生的变更，不写库');
    }

    public function handle(): void
    {
        $scanned = $this->scanAnnotations();

        if ($scanned === []) {
            $this->warn('未扫描到任何 #[Permission] 注解，请确认控制器已标注权限。');

            return;
        }

        $dryRun = (bool) $this->input->getOption('dry-run');
        $prune = (bool) $this->input->getOption('prune');

        $existing = SystemPermission::query()->get()->keyBy('code');
        $created = $updated = 0;

        foreach ($scanned as $code => $meta) {
            $current = $existing->get($code);

            if ($current === null) {
                ++$created;
                $this->line("  <fg=green>+ 新增</> {$code}  {$meta['name']}");

                if (! $dryRun) {
                    SystemPermission::query()->create($meta + ['is_synced' => SystemPermission::SYNCED_YES]);
                }

                continue;
            }

            // 只在元数据实际变化时更新，避免无意义的写入
            $changes = array_filter(
                $meta,
                static fn ($value, string $field): bool => (string) $current->{$field} !== (string) $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($current->is_synced !== SystemPermission::SYNCED_YES) {
                $changes['is_synced'] = SystemPermission::SYNCED_YES;
            }

            if ($changes !== []) {
                ++$updated;
                $this->line("  <fg=yellow>~ 更新</> {$code}  " . implode(', ', array_keys($changes)));

                if (! $dryRun) {
                    $current->update($changes);
                }
            }
        }

        $obsolete = $existing->keys()->diff(array_keys($scanned))->values()->all();
        $this->reportObsolete($obsolete, $prune, $dryRun);

        $this->newLine();
        $this->info(sprintf(
            '%s扫描 %d 项：新增 %d，更新 %d，代码已移除 %d。',
            $dryRun ? '[dry-run] ' : '',
            count($scanned),
            $created,
            $updated,
            count($obsolete),
        ));

        if (! $dryRun && ($created > 0 || $updated > 0 || $obsolete !== [])) {
            $this->permissionLogic->flushAllCache();
            $this->info('已清除全部用户的权限缓存。');
        }
    }

    /**
     * 处理代码中已移除的权限点.
     *
     * @param string[] $obsolete
     */
    private function reportObsolete(array $obsolete, bool $prune, bool $dryRun): void
    {
        if ($obsolete === []) {
            return;
        }

        foreach ($obsolete as $code) {
            $label = $prune ? '<fg=red>- 删除</>' : '<fg=red>! 失效</>';
            $this->line("  {$label} {$code}");
        }

        if ($dryRun) {
            return;
        }

        if ($prune) {
            $ids = SystemPermission::query()->whereIn('code', $obsolete)->pluck('id')->all();

            // 先撤授权再删权限点，避免留下悬空关联
            \Hyperf\DbConnection\Db::table('system_role_permission')->whereIn('permission_id', $ids)->delete();
            \Hyperf\DbConnection\Db::table('system_menu')->whereIn('permission_id', $ids)->update(['permission_id' => null]);
            SystemPermission::query()->whereIn('id', $ids)->delete();

            return;
        }

        SystemPermission::query()
            ->whereIn('code', $obsolete)
            ->update(['is_synced' => SystemPermission::SYNCED_NO]);

        $this->warn('以上权限点在代码中已不存在，已标记为失效。确认无用后可执行 permission:sync --prune 清理。');
    }

    /**
     * 扫描全部 #[Permission] 注解，合并路由信息.
     *
     * @return array<string, array<string, mixed>>
     */
    private function scanAnnotations(): array
    {
        $routeMap = $this->buildRouteMap();
        $result = [];

        foreach (AnnotationCollector::getMethodsByAnnotation(Permission::class) as $item) {
            /** @var Permission $annotation */
            $annotation = $item['annotation'];

            if (! $annotation->isValid()) {
                $this->warn("跳过 {$item['class']}@{$item['method']}：权限码为空。");

                continue;
            }

            $group = AnnotationCollector::getClassAnnotation($item['class'], PermissionGroup::class);
            $handler = $item['class'] . '@' . $item['method'];
            $route = $routeMap[$handler] ?? ['method' => '', 'path' => ''];

            foreach ($annotation->codes as $code) {
                // 同一权限码被多个方法声明时以首次出现为准，
                // 后续仅在缺失路由信息时补全
                if (isset($result[$code])) {
                    continue;
                }

                $result[$code] = [
                    'code' => $code,
                    'name' => $annotation->name !== '' ? $annotation->name : $code,
                    'module' => $annotation->resolveModule($code),
                    'module_name' => $group instanceof PermissionGroup ? $group->name : '',
                    'module_sort' => $group instanceof PermissionGroup ? $group->sort : 0,
                    'http_method' => $route['method'],
                    'http_path' => $route['path'],
                    'handler' => $handler,
                    'sort' => $annotation->sort,
                ];
            }
        }

        return $result;
    }

    /**
     * 建立 Controller@method => 路由 的映射，用于回填审计字段.
     *
     * @return array<string, array{method: string, path: string}>
     */
    private function buildRouteMap(): array
    {
        $map = [];
        [$staticRoutes, $variableRoutes] = $this->dispatcherFactory->getRouter('http')->getData();

        foreach ($staticRoutes as $httpMethod => $routes) {
            foreach ($routes as $handler) {
                $this->collectRoute($map, (string) $httpMethod, $handler);
            }
        }

        foreach ($variableRoutes as $httpMethod => $chunks) {
            foreach ($chunks as $chunk) {
                foreach ($chunk['routeMap'] ?? [] as $entry) {
                    $this->collectRoute($map, (string) $httpMethod, $entry[0] ?? null);
                }
            }
        }

        return $map;
    }

    /**
     * 从路由 Handler 中提取控制器方法标识.
     *
     * @param array<string, array{method: string, path: string}> $map
     */
    private function collectRoute(array &$map, string $httpMethod, mixed $handler): void
    {
        if (! $handler instanceof Handler) {
            return;
        }

        $callback = $handler->callback;
        $key = null;

        if (is_array($callback) && count($callback) === 2 && is_string($callback[0]) && is_string($callback[1])) {
            $key = $callback[0] . '@' . $callback[1];
        } elseif (is_string($callback) && str_contains($callback, '@')) {
            $key = $callback;
        }

        if ($key === null || isset($map[$key])) {
            return;
        }

        $map[$key] = ['method' => $httpMethod, 'path' => $handler->route];
    }
}
