<?php

declare(strict_types=1);

namespace App\System\Logic;

use App\Exception\BusinessException;
use Hyperf\HttpMessage\Upload\UploadedFile;

/**
 * 本地文件上传.
 *
 * 文件落在 BASE_PATH/public/uploads/{dir}/{Ym}/，
 * 通过 UploadController::show 对外提供访问（项目直连 Swoole，没有 nginx 静态目录）。
 */
class UploadLogic
{
    /**
     * 允许的图片扩展名.
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    /**
     * 允许的图片 MIME.
     */
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];

    /**
     * 图片体积上限（5MB）.
     */
    public const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

    /**
     * 上传根目录名.
     */
    public const UPLOAD_ROOT = 'public/uploads';

    /**
     * 保存图片，返回可访问的相对地址.
     *
     * @param string $dir 业务子目录，只允许字母数字下划线中划线
     * @return array{url: string, path: string, name: string, size: int}
     */
    public function saveImage(?UploadedFile $file, string $dir = 'signature'): array
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw new BusinessException('请选择要上传的文件');
        }

        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            throw new BusinessException('图片大小不能超过 5MB');
        }

        $extension = strtolower($file->getExtension());
        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            throw new BusinessException('只支持 ' . implode('、', self::IMAGE_EXTENSIONS) . ' 格式的图片');
        }

        // 扩展名可以伪造，再按真实 MIME 卡一道
        if (! in_array($file->getMimeType(), self::IMAGE_MIMES, true)) {
            throw new BusinessException('文件内容不是有效的图片');
        }

        $dir = $this->normalizeDir($dir);
        $subPath = $dir . '/' . date('Ym');
        $absoluteDir = BASE_PATH . '/' . self::UPLOAD_ROOT . '/' . $subPath;

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            throw new BusinessException('上传目录创建失败，请检查目录权限');
        }

        // 随机文件名，避免原始文件名带来的路径问题和覆盖
        $filename = date('YmdHis') . '_' . generate_code(10) . '.' . $extension;
        $file->moveTo($absoluteDir . '/' . $filename);

        $relativePath = $subPath . '/' . $filename;

        return [
            'url' => '/uploads/' . $relativePath,
            'path' => $relativePath,
            'name' => $file->getClientFilename() ?? $filename,
            'size' => $file->getSize(),
        ];
    }

    /**
     * 把相对路径解析成磁盘绝对路径，越权路径一律拒绝.
     */
    public function resolvePath(string $relativePath): string
    {
        // 挡掉 ../ 穿越和 URL 编码变体
        $decoded = rawurldecode($relativePath);
        if ($decoded === '' || str_contains($decoded, '..') || str_contains($decoded, "\0")) {
            throw new BusinessException('文件路径不合法');
        }

        $root = realpath(BASE_PATH . '/' . self::UPLOAD_ROOT);
        if ($root === false) {
            throw new BusinessException('文件不存在');
        }

        $target = realpath($root . '/' . ltrim($decoded, '/'));

        // realpath 解析后必须仍在上传根目录内
        if ($target === false || ! str_starts_with($target, $root . DIRECTORY_SEPARATOR) || ! is_file($target)) {
            throw new BusinessException('文件不存在');
        }

        return $target;
    }

    /**
     * 按扩展名推断 Content-Type.
     */
    public function guessContentType(string $absolutePath): string
    {
        return match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => 'application/octet-stream',
        };
    }

    /**
     * 过滤业务子目录名.
     */
    protected function normalizeDir(string $dir): string
    {
        $dir = trim($dir, '/');

        if ($dir === '' || preg_match('/^[A-Za-z0-9_-]+$/', $dir) !== 1) {
            return 'signature';
        }

        return $dir;
    }
}
