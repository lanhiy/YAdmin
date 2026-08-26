<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Annotation\WithoutPermission;
use App\Controller\AbstractController;
use App\System\Logic\PermissionTreeLogic;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '权限管理', sort: 12)]
class PermissionController extends AbstractController
{
    #[Inject]
    protected PermissionTreeLogic $logic;

    /**
     * 权限树（角色授权界面用）.
     *
     * 与角色详情共用授权语义：能编辑角色的人才需要看到完整权限树，
     * 因此复用 system:role:edit，不额外造权限点。
     */
    #[Permission(['system:role:edit', 'system:role:add'], '获取权限树', sort: 1)]
    public function tree(): ResponseInterface
    {
        return $this->success($this->logic->getTree());
    }

    /**
     * 权限点列表（权限点管理/审计用）.
     */
    #[Permission('system:permission:list', '查看权限点', sort: 2)]
    public function list(RequestInterface $request): ResponseInterface
    {
        return $this->success($this->logic->getList($request->all()));
    }
}
