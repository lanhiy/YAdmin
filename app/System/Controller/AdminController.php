<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\AdminLogic;
use App\System\Request\SystemAdminRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '用户配置', sort: 12)]
class AdminController extends AbstractController
{
    #[Inject]
    protected AdminLogic $logic;

    /**
     * 获取用户列表（分页）
     */
    #[Permission('system:admin:list', '查看列表', sort: 1)]
    public function list(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        $data = $this->logic->getAdminList($params);
        return $this->success($data);
    }

    /**
     * 获取用户详情
     */
    #[Permission('system:admin:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getAdminById($id);
        return $this->success($data);
    }

    /**
     * 新增用户
     */
    #[Scene(scene: 'save')]
    #[Permission('system:admin:add', '新增', sort: 4)]
    public function store(SystemAdminRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->createAdmin($data);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新用户
     */
    #[Scene(scene: 'update')]
    #[Permission('system:admin:edit', '编辑', sort: 5)]
    public function update(int $id, SystemAdminRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $result = $this->logic->updateAdmin($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除用户
     */
    #[Permission('system:admin:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteAdmin($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改用户状态
     */
    #[Scene(scene: 'changeStatus')]
    #[Permission('system:admin:status', '修改状态', sort: 7)]
    public function changeStatus(SystemAdminRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}