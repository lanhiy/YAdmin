<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\VerificationCertLogic;
use App\System\Request\Business\VerificationCertRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class VerificationCertController extends AbstractController
{
    #[Inject]
    protected VerificationCertLogic $logic;

    /**
     * 按产品ID获取检定证书，未录入返回 null.
     */
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取检定证书详情.
     */
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增检定证书.
     */
    #[Scene(scene: 'save')]
    public function store(VerificationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->create($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 更新检定证书.
     */
    #[Scene(scene: 'update')]
    public function update(int $id, VerificationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除检定证书.
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 修改检定证书状态.
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(VerificationCertRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int) $data['id'], (int) $data['status']);

        return $this->success(null, '状态修改成功');
    }
}
