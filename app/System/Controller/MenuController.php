<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\WithoutPermission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\MenuLogic;
use App\System\Request\SystemMenuRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '菜单配置', sort: 10)]
class MenuController extends AbstractController
{
    #[Inject]
    protected MenuLogic $logic;

    /**
     * 获取菜单列表（树形）- 包含按钮
     */
    #[Permission('system:menu:list', '查看列表', sort: 1)]
    public function list(RequestInterface $request): ResponseInterface
    {
        $data = $this->logic->getMenuTree();
        return $this->success($data);
    }

    /**
     * 获取当前用户的路由菜单
     */
    #[WithoutPermission('前端渲染自身可见菜单，权限已在数据层按用户过滤')]
    public function routes(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');
        $data = $this->logic->getUserRoutes($adminId);
        return $this->success($data);
    }

    /**
     * 获取当前用户的按钮权限
     */
    #[WithoutPermission('返回的是自身按钮权限，与 user/access-codes 同义')]
    public function buttons(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');
        $data = $this->logic->getUserButtonPermissions($adminId);
        return $this->success($data);
    }

    /**
     * 获取菜单详情
     */
    #[Permission('system:menu:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getMenuById($id);
        return $this->success($data);
    }

    /**
     * 新增菜单
     */
    #[Scene(scene: 'save')]
    #[Permission('system:menu:add', '新增', sort: 4)]
    public function store(SystemMenuRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->createMenu($data);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新菜单
     */
    #[Scene(scene: 'update')]
    #[Permission('system:menu:edit', '编辑', sort: 5)]
    public function update(int $id, SystemMenuRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateMenu($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除菜单
     */
    #[Permission('system:menu:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteMenu($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改菜单状态
     */
    #[Scene(scene: 'changeStatus')]
    #[Permission('system:menu:status', '修改状态', sort: 7)]
    public function changeStatus(SystemMenuRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}