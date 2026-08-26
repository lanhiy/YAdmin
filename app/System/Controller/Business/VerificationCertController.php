<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\Business\VerificationCertLogic;
use App\System\Request\Business\VerificationCertRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '检定证书', sort: 22)]
class VerificationCertController extends AbstractController
{
    #[Inject]
    protected VerificationCertLogic $logic;

    /**
     * 按产品ID获取检定证书，未录入返回 null.
     */
    #[Permission('system:verificationCert:show', '查看详情', sort: 3)]
    public function byProduct(int $productId): ResponseInterface
    {
        return $this->success($this->logic->getByProductId($productId));
    }

    /**
     * 获取检定证书详情.
     */
    #[Permission('system:verificationCert:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getById($id));
    }

    /**
     * 新增检定证书.
     */
    #[Scene(scene: 'save')]
    #[Permission('system:verificationCert:add', '新增', sort: 4)]
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
    #[Permission('system:verificationCert:edit', '编辑', sort: 5)]
    public function update(int $id, VerificationCertRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->update($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除检定证书.
     */
    #[Permission('system:verificationCert:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->delete($id);

        return $this->success(null, '删除成功');
    }

}
