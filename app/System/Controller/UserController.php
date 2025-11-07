<?php

namespace App\System\Controller;

use App\Controller\AbstractController;
use App\System\Logic\UserLogic;
use App\System\Request\SystemAdminRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

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
    public function userInfo(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        return $this->success($this->logic->getUserInfo($adminId));
    }

    /**
     * 获取个人资料
     */
    public function getProfile(RequestInterface $request): ResponseInterface
    {
        $adminId = $request->getAttribute("admin_id");
        return $this->success($this->logic->getProfile($adminId));
    }

    /**
     * 更新个人资料
     */
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
    public function logout(RequestInterface $request): ResponseInterface
    {
        // TODO: 实现退出逻辑，如果需要的话可以加入黑名单
        return $this->success(null, '退出成功');
    }
}