<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\Business\ProductLogic;
use App\System\Request\Business\ProductRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '产品管理', sort: 20)]
class ProductController extends AbstractController
{
    #[Inject]
    protected ProductLogic $logic;

    /**
     * 获取产品列表（分页）.
     */
    #[Permission('system:product:list', '查看列表', sort: 1)]
    public function list(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->logic->getProductList($request->all()));
    }

    /**
     * 产品下拉选项.
     */
    #[Permission('system:product:options', '下拉选项', sort: 2)]
    public function options(): ResponseInterface
    {
        return $this->success($this->logic->getProductOptions());
    }

    /**
     * 获取产品详情（含三张子表）.
     */
    #[Permission('system:product:show', '查看详情', sort: 3)]
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getProductById($id));
    }

    /**
     * 新增产品.
     */
    #[Scene(scene: 'save')]
    #[Permission('system:product:add', '新增', sort: 4)]
    public function store(ProductRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->createProduct($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 复制产品及其已有报告/证书。
     */
    #[Permission('system:product:copy', '复制', sort: 7)]
    public function copy(int $id, RequestInterface $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->copyProduct($id, $adminId);

        return $this->success($result, '复制成功');
    }

    /**
     * 获取指定 PDF 模板所需的产品及单据数据。
     *
     * 当前只负责取数，实际 PDF 文件生成在后续模板接入后完成。
     */
    #[Permission('system:product:generatePdf', '生成PDF', sort: 8)]
    public function pdfData(int $certificateId, RequestInterface $request): ResponseInterface
    {
        $type = trim((string) $request->input('type', ''));

        return $this->success($this->logic->getPdfData($certificateId, $type), '证书数据获取成功');
    }

    /**
     * 更新产品.
     */
    #[Scene(scene: 'update')]
    #[Permission('system:product:edit', '编辑', sort: 5)]
    public function update(int $id, ProductRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->updateProduct($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除产品（级联删除三张子表数据）.
     */
    #[Permission('system:product:delete', '删除', sort: 6)]
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteProduct($id);

        return $this->success(null, '删除成功');
    }

}
