<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\Business\TestReportLogic;
use App\System\Request\Business\TestReportRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '测试报告', sort: 21)]
class TestReportController extends AbstractController
{
    #[Inject]
    protected TestReportLogic $logic;

    /**
     * 按产品ID获取测试报告，未录入返回 null.
     */
    #[Permission('system:testReport:show', '查看详情', sort: 3)]
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取测试报告详情.
     */
    #[Permission('system:testReport:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增测试报告.
     */
    #[Scene(scene: 'save')]
    #[Permission('system:testReport:add', '新增', sort: 4)]
    public function store(TestReportRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->create($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 更新测试报告.
     */
    #[Scene(scene: 'update')]
    #[Permission('system:testReport:edit', '编辑', sort: 5)]
    public function update(int $id, TestReportRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除测试报告.
     */
    #[Permission('system:testReport:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

}
