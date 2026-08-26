<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\RoleLogic;
use App\System\Request\SystemRoleRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '角色配置', sort: 11)]
class RoleController extends AbstractController
{
    #[Inject]
    protected RoleLogic $logic;

    /**
     * 获取角色列表（分页）
     */
    #[Permission('system:role:list', '查看列表', sort: 1)]
    public function list(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        $data = $this->logic->getRoleList($params);
        return $this->success($data);
    }

    /**
     * 获取所有角色（下拉选择）
     */
    #[Permission('system:role:all', '获取全部', sort: 2)]
    public function all(RequestInterface $request): ResponseInterface
    {
        $data = $this->logic->getAllRoles();
        return $this->success($data);
    }

    /**
     * 获取角色详情
     */
    #[Permission('system:role:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getRoleById($id);
        return $this->success($data);
    }

    /**
     * 新增角色
     */
    #[Scene(scene: 'save')]
    #[Permission('system:role:add', '新增', sort: 4)]
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
    #[Permission('system:role:edit', '编辑', sort: 5)]
    public function update(int $id, SystemRoleRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateRole($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除角色
     */
    #[Permission('system:role:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteRole($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改角色状态
     */
    #[Scene(scene: 'changeStatus')]
    #[Permission('system:role:status', '修改状态', sort: 7)]
    public function changeStatus(SystemRoleRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}