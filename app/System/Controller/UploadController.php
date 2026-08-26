<?php

declare(strict_types=1);

namespace App\System\Controller;

use App\Annotation\Permission;
use App\Annotation\WithoutPermission;
use App\Annotation\PermissionGroup;
use App\Controller\AbstractController;
use App\System\Logic\UploadLogic;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[PermissionGroup(name: '文件上传', sort: 14)]
class UploadController extends AbstractController
{
    #[Inject]
    protected UploadLogic $logic;

    /**
     * 上传图片（签名图片等）.
     *
     * 需要登录，前端 FormData 字段名为 file，可选 dir 指定业务子目录。
     */
    #[Permission('system:upload:image', '上传图片', sort: 1)]
    public function image(RequestInterface $request): ResponseInterface
    {
        $file = $request->file('file');
        $dir = (string) $request->input('dir', 'signature');

        $result = $this->logic->saveImage($file, $dir);

        return $this->success($result, '上传成功');
    }

    /**
     * 读取已上传的图片.
     *
     * 这个路由不挂 JWT 中间件：img 标签无法携带 Authorization 头。
     * 上传目录里只有签名图片这类需要公开展示的静态资源，
     * 路径穿越已在 UploadLogic::resolvePath 中拦截。
     */
    #[WithoutPermission('图片读取，img 标签无法携带 Authorization 头')]
    public function show(string $path): ResponseInterface
    {
        $absolutePath = $this->logic->resolvePath($path);
        $contents = (string) file_get_contents($absolutePath);

        return $this->response
            ->withHeader('Content-Type', $this->logic->guessContentType($absolutePath))
            ->withHeader('Content-Length', (string) strlen($contents))
            ->withHeader('Cache-Control', 'public, max-age=604800')
            ->withBody(new SwooleStream($contents));
    }
}
