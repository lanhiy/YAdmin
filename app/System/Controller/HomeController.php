<?php

namespace App\System\Controller;

use App\System\Request\SystemAdminRequest;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\Annotation\Scene;

#[Controller(prefix: 'system')]
class HomeController
{
    /**
     * 用户登录
     */
    #[PostMapping('login')]
    #[Scene(scene: 'login')]
    public function login(SystemAdminRequest $request)
    {
        // 注解会自动验证，这里直接使用即可
        $data = $request->validated();

        var_dump($data);

        return [
            'code' => 200,
            'message' => 'success',
            'data' => $data
        ];
    }
}