<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Controller\AbstractController;
use App\System\Logic\MenuLogic;
use App\System\Request\SystemMenuRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class MenuController extends AbstractController
{
    #[Inject]
    protected MenuLogic $logic;

    /**
     * 获取菜单列表（树形）
     */
    public function list(RequestInterface $request): ResponseInterface
    {
        $data = $this->logic->getMenuTree();
        return $this->success($data);
    }

    /**
     * 获取当前用户的路由菜单
     */
    public function routes(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');
        $data = $this->logic->getUserRoutes($adminId);
        return $this->success($data);
    }

    /**
     * 获取菜单详情
     */
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getMenuById($id);
        return $this->success($data);
    }

    /**
     * 新增菜单
     */
    #[Scene(scene: 'save')]
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
    public function update(int $id, SystemMenuRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateMenu($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除菜单
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteMenu($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改菜单状态
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(SystemMenuRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}