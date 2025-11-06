<?php

namespace App\System\Controller;

use App\Controller\AbstractController;
use App\System\Logic\LoginLogic;
use App\System\Request\SystemAdminRequest;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: 'system')]
class HomeController extends AbstractController
{
    #[Inject]
    protected  LoginLogic $logic;

    /**
     * 用户登录
     */
    #[PostMapping('login')]
    #[Scene(scene: 'login')]
    public function login(SystemAdminRequest $request): ResponseInterface
    {
        // 注解会自动验证，这里直接使用即可
        $data = $request->validated();
        $data['ip'] = get_client_ip();
        return $this->success($this->logic->adminLogin($data));
    }
}