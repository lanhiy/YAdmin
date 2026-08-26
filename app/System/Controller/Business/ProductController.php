<?php

declare(strict_types=1);

namespace App\System\Controller\Business;

use App\Controller\AbstractController;
use App\System\Logic\Business\ProductLogic;
use App\System\Request\Business\ProductRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

class ProductController extends AbstractController
{
    #[Inject]
    protected ProductLogic $logic;

    /**
     * 获取产品列表（分页）.
     */
    public function list(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->logic->getProductList($request->all()));
    }

    /**
     * 产品下拉选项.
     */
    public function options(): ResponseInterface
    {
        return $this->success($this->logic->getProductOptions());
    }

    /**
     * 获取产品详情（含三张子表）.
     */
    public function show(int $id): ResponseInterface
    {
        return $this->success($this->logic->getProductById($id));
    }

    /**
     * 新增产品.
     */
    #[Scene(scene: 'save')]
    public function store(ProductRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->createProduct($request->validated(), $adminId);

        return $this->success($result, '创建成功');
    }

    /**
     * 更新产品.
     */
    #[Scene(scene: 'update')]
    public function update(int $id, ProductRequest $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute('admin_id');
        $result = $this->logic->updateProduct($id, $request->validated(), $adminId);

        return $this->success($result, '更新成功');
    }

    /**
     * 删除产品（级联删除三张子表数据）.
     */
    public function destroy(int $id): ResponseInterface
    {
        $this->logic->deleteProduct($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 修改产品状态.
     */
    #[Scene(scene: 'changeStatus')]
    public function changeStatus(ProductRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $this->logic->changeStatus((int) $data['id'], (int) $data['status']);

        return $this->success(null, '状态修改成功');
    }
}
