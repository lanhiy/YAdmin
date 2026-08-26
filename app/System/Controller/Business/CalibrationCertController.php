<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\Business\CalibrationCertLogic;
use App\System\Request\Business\CalibrationCertRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '校准证书', sort: 23)]
class CalibrationCertController extends AbstractController
{
    #[Inject]
    protected CalibrationCertLogic $logic;

    /**
     * 按产品ID获取校准证书，未录入返回 null.
     */
    #[Permission('system:calibrationCert:show', '查看详情', sort: 3)]
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取校准证书详情.
     */
    #[Permission('system:calibrationCert:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增校准证书.
     */
    #[Scene(scene: 'save')]
    #[Permission('system:calibrationCert:add', '新增', sort: 4)]
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
    #[Permission('system:calibrationCert:edit', '编辑', sort: 5)]
    public function update(int $id, CalibrationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除校准证书.
     */
    #[Permission('system:calibrationCert:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

}
