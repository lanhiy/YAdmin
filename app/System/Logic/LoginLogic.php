<?php

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdmin;

class LoginLogic
{
    public function adminLogin(array $data): array
    {
        $username = (string)($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');
        $ip = (string)($data['ip'] ?? '');

        // 查询用户
        /** @var SystemAdmin|null $user */
        $user = SystemAdmin::query()
            ->where('username', $username)
            ->first();

        // 用户不存在
        if (!$user instanceof SystemAdmin) {
            throw new BusinessException('账号或密码错误');
        }

        // 验证密码
        $hashedPassword = $user->getAttributes()['password'] ?? '';
        if (!SystemAdmin::passwordVerify($password, $hashedPassword)) {
            throw new BusinessException('账号或密码错误');
        }

        if($user->status !==1) throw new BusinessException('用户状态异常,暂时被禁止登录');

        // 更新登录信息
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $ip;
        $user->save();

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
        ];
    }
}