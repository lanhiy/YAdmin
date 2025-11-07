<?php

namespace App\System\Logic;

use App\Exception\BusinessException;
use App\Model\SystemAdmin;
use HyperfExtension\Jwt\Contracts\ManagerInterface;
use Hyperf\DbConnection\Db;

class UserLogic
{
    protected $manager;

    public function __construct(
        ManagerInterface $manager,
    ) {
        $this->manager = $manager;
    }

    /**
     * 管理员登录
     */
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

    /**
     * 获取用户信息（用于权限验证）
     */
    public function getUserInfo(int $adminId): array
    {
        $user = SystemAdmin::query()->where('id', $adminId)->first();

        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        return [
            'roles' => [],
            'realName' => $user->getAttributes()['nickname'],
            'email' => $user->getAttributes()['email'],
            'mobile' => $user->getAttributes()['mobile'],
            'avatar' => $user->getAttributes()['avatar'],
            'gender' => $user->getAttributes()['gender'],
        ];
    }

    /**
     * 获取个人资料
     */
    public function getProfile(int $adminId): array
    {
        $user = SystemAdmin::query()->where('id', $adminId)->first();

        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        return [
            'username' => $user->username,
            'nickname' => $user->nickname ?? '',
            'mobile' => $user->mobile ?? '',
            'email' => $user->email ?? '',
            'gender' => $user->gender ?? 0,
            'avatar' => $user->avatar ?? '',
            'remark' => $user->remark ?? '',
        ];
    }

    /**
     * 更新个人资料
     */
    public function updateProfile(int $adminId, array $data): void
    {
        $user = SystemAdmin::query()->where('id', $adminId)->first();

        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        // 开启事务
        Db::beginTransaction();
        try {
            // 更新字段
            if (isset($data['nickname'])) {
                $user->nickname = $data['nickname'];
            }
            if (isset($data['mobile'])) {
                // 检查手机号是否被其他用户使用
                if ($data['mobile']) {
                    $exists = SystemAdmin::query()
                        ->where('mobile', $data['mobile'])
                        ->where('id', '!=', $adminId)
                        ->exists();
                    if ($exists) {
                        throw new BusinessException('该手机号已被使用');
                    }
                }
                $user->mobile = $data['mobile'];
            }
            if (isset($data['email'])) {
                // 检查邮箱是否被其他用户使用
                if ($data['email']) {
                    $exists = SystemAdmin::query()
                        ->where('email', $data['email'])
                        ->where('id', '!=', $adminId)
                        ->exists();
                    if ($exists) {
                        throw new BusinessException('该邮箱已被使用');
                    }
                }
                $user->email = $data['email'];
            }
            if (isset($data['gender'])) {
                $user->gender = $data['gender'];
            }
            if (isset($data['avatar'])) {
                $user->avatar = $data['avatar'];
            }
            if (isset($data['remark'])) {
                $user->remark = $data['remark'];
            }

            $user->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BusinessException($e->getMessage());
        }
    }

    /**
     * 修改密码
     */
    public function changePassword(int $adminId, array $data): void
    {
        $user = SystemAdmin::query()->where('id', $adminId)->first();

        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        // 验证旧密码
        $hashedPassword = $user->getAttributes()['password'] ?? '';
        if (!SystemAdmin::passwordVerify($data['oldPassword'], $hashedPassword)) {
            throw new BusinessException('旧密码错误');
        }

        // 验证新密码与旧密码不同
        if ($data['oldPassword'] === $data['newPassword']) {
            throw new BusinessException('新密码不能与旧密码相同');
        }

        // 开启事务
        Db::beginTransaction();
        try {
            // 更新密码
            $user->password = SystemAdmin::passwordHash($data['newPassword']);
            $user->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            throw new BusinessException('密码修改失败');
        }
    }

    /**
     * 上传头像
     *
     * @param int $adminId
     * @param mixed $file
     * @return string 返回头像URL
     */
    public function uploadAvatar(int $adminId, $file): string
    {
        // TODO: 实现文件上传逻辑
        // 1. 验证文件类型和大小
        // 2. 生成文件名
        // 3. 保存文件
        // 4. 更新用户头像字段
        // 5. 返回文件URL

        throw new BusinessException('文件上传功能待实现');
    }
}