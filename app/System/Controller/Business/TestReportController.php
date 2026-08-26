<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\TestReportLogic;
use App\System\Request\Business\TestReportRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class TestReportController extends AbstractController
{
    #[Inject]
    protected TestReportLogic $logic;

    /**
     * 按产品ID获取测试报告，未录入返回 null.
     */
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取测试报告详情.
     */
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增测试报告.
     */
    #[Scene(scene: 'save')]
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
    public function update(int $id, TestReportRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除测试报告.
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 修改测试报告状态.
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(TestReportRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int) $data['id'], (int) $data['status']);

        return $this->success(null, '状态修改成功');
    }
}
