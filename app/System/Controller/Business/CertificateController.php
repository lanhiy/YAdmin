<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\CertificateLogic;
use App\System\Request\Business\CertificateRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class CertificateController extends AbstractController
{
    #[Inject]
    protected CertificateLogic $logic;

    /**
     * 获取证书列表（分页）
     */
    public function list(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        $data = $this->logic->getCertificateList($params);
        return $this->success($data);
    }

    /**
     * 获取证书详情
     */
    public function show(int $id): ResponseInterface
    {
        $data = $this->logic->getCertificateById($id);
        return $this->success($data);
    }

    /**
     * 新增证书
     */
    #[Scene(scene: 'save')]
    public function store(CertificateRequest $request): ResponseInterface
    {
        $adminId = (int)$request->getAttribute('admin_id');
        $data = $request->validated();
        $result = $this->logic->createCertificate($data, $adminId);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新证书
     */
    #[Scene(scene: 'update')]
    public function update(int $id, CertificateRequest $request): ResponseInterface
    {
        $adminId = (int)$request->getAttribute('admin_id');
        $data = $request->validated();
        $result = $this->logic->updateCertificate($id, $data, $adminId);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除证书
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteCertificate($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 修改证书状态
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(CertificateRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int)$data['id'], (int)$data['status']);
        return $this->success(null, '状态修改成功');
    }
}
