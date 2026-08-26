<?php

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\PermissionGroup;
use App\Annotation\WithoutPermission;
use App\Controller\AbstractController;
use App\System\Logic\UserLogic;
use App\System\Request\SystemAdminRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '个人设置', sort: 90)]
class UserController extends AbstractController
{
    #[Inject]
    protected UserLogic $logic;

    /**
     * 用户登录
     */
    #[Scene(scene: 'login')]
    public function login(SystemAdminRequest $request): ResponseInterface
    {
        $data = $request->validated();
        $data['ip'] = get_client_ip();
        return $this->success($this->logic->adminLogin($data));
    }

    /**
     * 获取用户信息
     */
    #[WithoutPermission('登录后获取自身信息，是前端初始化的必要接口')]
    public function userInfo(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        return $this->success($this->logic->getUserInfo($adminId));
    }

    /**
     * 获取当前用户权限码（前端按钮级权限用）
     */
    #[WithoutPermission('返回的就是自身权限，用权限保护它会造成循环依赖')]
    public function accessCodes(RequestInterface $request): ResponseInterface
    {
        $adminId = (int) $request->getAttribute("admin_id");
        return $this->success($this->logic->getUserAccessCodes($adminId));
    }

    /**
     * 获取个人资料
     */
    #[Permission('profile:show', '查看资料', sort: 1)]
    public function getProfile(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        return $this->success($this->logic->getProfile($adminId));
    }

    /**
     * 更新个人资料
     */
    #[Permission('profile:update', '更新资料', sort: 2)]
    #[Scene(scene: 'modifyUserInfo')]
    public function updateProfile(SystemAdminRequest $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        $data = $request->validated();
        $this->logic->updateProfile($adminId, $data);
        return $this->success(null, '更新成功');
    }

    /**
     * 修改密码
     */
    #[Permission('profile:changePassword', '修改密码', sort: 3)]
    #[Scene(scene: 'modifyPassword')]
    public function changePassword(SystemAdminRequest $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        $data = $request->validated();
        $this->logic->changePassword($adminId, $data);
        return $this->success(null, '密码修改成功');
    }

    /**
     * 上传头像
     */
    #[Permission('profile:uploadAvatar', '上传头像', sort: 4)]
    public function uploadAvatar(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");

        // TODO: 实现文件上传逻辑
        // $file = $request->file('file');
        // $avatarUrl = $this->logic->uploadAvatar($adminId, $file);

        return $this->success([
            'avatar' => 'https://via.placeholder.com/150' // 临时返回
        ], '头像上传成功');
    }

    /**
     * 退出登录
     */
    #[WithoutPermission('已登录用户都应能登出')]
    public function logout(RequestInterface $request): ResponseInterface
    {
        // TODO: 实现退出逻辑，如果需要的话可以加入黑名单
        return $this->success(null, '退出成功');
    }
}