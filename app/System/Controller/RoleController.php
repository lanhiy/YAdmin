<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Controller\AbstractController;
use App\System\Logic\RoleLogic;
use App\System\Request\SystemRoleRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class RoleController extends AbstractController
{
    #[Inject]
    protected RoleLogic $logic;

    /**
     * 获取角色列表（分页）
     */
    public function list(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        $data = $this->logic->getRoleList($params);
        return $this->success($data);
    }

    /**
     * 获取所有角色（下拉选择）
     */
    public function all(RequestInterface $request): ResponseInterface
    {
        $data = $this->logic->getAllRoles();
        return $this->success($data);
    }

    /**
     * 获取角色详情
     */
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getRoleById($id);
        return $this->success($data);
    }

    /**
     * 新增角色
     */
    #[Scene(scene: 'save')]
    public function store(SystemRoleRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->createRole($data);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新角色
     */
    #[Scene(scene: 'update')]
    public function update(int $id, SystemRoleRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateRole($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除角色
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteRole($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改角色状态
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(SystemRoleRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}