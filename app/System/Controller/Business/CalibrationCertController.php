<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\CalibrationCertLogic;
use App\System\Request\Business\CalibrationCertRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class CalibrationCertController extends AbstractController
{
    #[Inject]
    protected CalibrationCertLogic $logic;

    /**
     * 按产品ID获取校准证书，未录入返回 null.
     */
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取校准证书详情.
     */
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增校准证书.
     */
    #[Scene(scene: 'save')]
    public function store(CalibrationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->create($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 更新校准证书.
     */
    #[Scene(scene: 'update')]
    public function update(int $id, CalibrationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除校准证书.
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 修改校准证书状态.
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(CalibrationCertRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int) $data['id'], (int) $data['status']);

        return $this->success(null, '状态修改成功');
    }
}
