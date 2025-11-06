<?php

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdmin;
use HyperfExtension\Jwt\Contracts\JwtFactoryInterface;
use HyperfExtension\Jwt\Contracts\ManagerInterface;

class LoginLogic
{
    protected $manager;

    public function __construct(
        ManagerInterface $manager,
    ) {
        $this->manager = $manager;
    }

    public function adminLogin(array $data): array
    {
        $username = (string)($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');
        $ip = (string)($data['ip'] ?? '');

        // 查询用户
        $user = SystemAdmin::query()
            ->where('username', $username)
            ->first();

        // 验证用户
        if (!$user instanceof SystemAdmin) {
            throw new BusinessException('账号或密码错误');
        }

        // 验证密码
        $hashedPassword = $user->getAttributes()['password'] ?? '';
        if (!SystemAdmin::passwordVerify($password, $hashedPassword)) {
            throw new BusinessException('账号或密码错误');
        }

        // 验证状态
        if ($user->status !== 1) {
            throw new BusinessException('用户状态异常,暂时被禁止登录');
        }

        // 更新登录信息
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $ip;
        $user->save();

        // 从 Manager 获取 PayloadFactory
        $payloadFactory = $this->manager->getPayloadFactory();

        $payload = $payloadFactory->make([
            'sub' => (string)$user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'admin_id' => $user->id,
        ]);

        $token = $this->manager->encode($payload)->get();


        return [
            'accessToken' => $token,
        ];
    }

    public function getUserInfo(int $adminId)
    {
        return ['roles'=>[],'realName'=>'蓝海'];
    }
}