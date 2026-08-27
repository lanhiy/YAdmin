<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Exception\BusinessException;
use App\Model\ProductCertificate;

class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    /**
     * 公开查询证书结果。
     *
     * URL 只包含一个随机公开令牌，统一表直接完成单次定位；结果页字段
     * 暂未确定，当前返回空数组。
     */
    public function certificate(string $token): array
    {
        $token = strtolower(trim($token));
        if (preg_match('/^[a-f0-9]{32}$/', $token) !== 1) {
            throw new BusinessException('证书链接无效');
        }

        if (! ProductCertificate::query()->where('public_token', $token)->exists()) {
            throw new BusinessException('证书不存在或链接无效');
        }

        return [];
    }
}
