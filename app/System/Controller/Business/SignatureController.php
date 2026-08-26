<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\SignatureLogic;
use App\System\Request\Business\SignatureRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class SignatureController extends AbstractController
{
    #[Inject]
    protected SignatureLogic $logic;

    /**
     * 获取签名列表（分页）.
     */
    public function list(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->logic->getSignatureList($request->all()));
    }

    /**
     * 启用状态的签名列表（单据表单签名选择器用）.
     */
    public function all(): ResponseInterface
    {
        return $this->success($this->logic->getEnabledSignatures());
    }

    /**
     * 获取签名详情.
     */
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getSignatureById($id));
    }

    /**
     * 新增签名.
     */
    #[Scene(scene: 'save')]
    public function store(SignatureRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->createSignature($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 更新签名.
     */
    #[Scene(scene: 'update')]
    public function update(int $id, SignatureRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->updateSignature($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除签名.
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteSignature($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 修改签名状态.
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(SignatureRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int) $data['id'], (int) $data['status']);

        return $this->success(null, '状态修改成功');
    }
}
