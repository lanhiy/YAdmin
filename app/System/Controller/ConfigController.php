<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\WithoutPermission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\ConfigLogic;
use App\System\Request\SystemConfigRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '系统配置', sort: 13)]
class ConfigController extends AbstractController
{
    #[Inject]
    protected ConfigLogic $logic;

    /**
     * 获取所有配置（前端初始化用，不需要认证）
     */
    #[WithoutPermission('前端初始化读取公开配置，该路由不在认证组内')]
    public function all(RequestInterface $request): ResponseInterface
    {
        $data = $this->logic->getAllConfig();
        return $this->success($data);
    }

    /**
     * 更新系统配置（前端表单提交用）
     * POST /system/config/update
     */
    #[Permission('system:config:edit', '编辑', sort: 5)]
    public function updateConfig(RequestInterface $request): ResponseInterface
    {
        $data = $request->all();

        // 批量更新配置
        $this->logic->batchUpdateConfig($data);

        return $this->success(null, '保存成功');
    }

    /**
     * 获取配置列表（分页，后台管理用）
     */
    #[Permission('system:config:list', '查看列表', sort: 1)]
    public function list(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        $data = $this->logic->getConfigList($params);
        return $this->success($data);
    }

    /**
     * 按类型获取配置
     */
    #[Permission('system:config:type', '按类型查询', sort: 9)]
    public function getByType(string $type): ResponseInterface
    {
        $data = $this->logic->getConfigByType($type);
        return $this->success($data);
    }

    /**
     * 获取配置详情
     */
    #[Permission('system:config:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getConfigById($id);
        return $this->success($data);
    }

    /**
     * 新增配置
     */
    #[Scene(scene: 'save')]
    #[Permission('system:config:add', '新增', sort: 4)]
    public function store(SystemConfigRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->createConfig($data);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新配置
     */
    #[Scene(scene: 'update')]
    #[Permission('system:config:edit', '编辑', sort: 5)]
    public function update(int $id, SystemConfigRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateConfig($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 批量更新配置
     */
    #[Scene(scene: 'batchUpdate')]
    #[Permission('system:config:batchUpdate', '批量更新', sort: 8)]
    public function batchUpdate(SystemConfigRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->batchUpdateConfig($data['configs']);
        return $this->success(null, '批量更新成功');
    }

    /**
     * 删除配置
     */
    #[Permission('system:config:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteConfig($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改配置状态
     */
    #[Scene(scene: 'changeStatus')]
    #[Permission('system:config:status', '修改状态', sort: 7)]
    public function changeStatus(SystemConfigRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}