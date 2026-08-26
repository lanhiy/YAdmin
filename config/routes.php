<?php

declare(strict_types=1);

use App\Middleware\JwtAuthMiddleware;
use App\Middleware\PermissionMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', [App\Controller\IndexController::class, 'index']);

// ========== 不需要认证的路由 ==========
Router::post('/system/login', [App\System\Controller\UserController::class, 'login']);

// ✅ 系统配置 - 前端初始化用（不需要认证）
Router::get('/system/config', [App\System\Controller\ConfigController::class, 'all']);

// 已上传文件的读取（img 标签无法携带 Authorization 头，故不挂 JWT 中间件）
Router::get('/uploads/{path:.+}', [App\System\Controller\UploadController::class, 'show']);

// ========== 需要认证的路由 ==========
Router::addGroup('/system', function () {
    // 用户相关
    Router::get('/user/info', [App\System\Controller\UserController::class, 'userInfo']);
    Router::get('/user/access-codes', [App\System\Controller\UserController::class, 'accessCodes']);
    Router::post('/user/logout', [App\System\Controller\UserController::class, 'logout']);

    // 个人中心路由
    Router::get('/profile', [App\System\Controller\UserController::class, 'getProfile']);
    Router::put('/profile', [App\System\Controller\UserController::class, 'updateProfile']);
    Router::post('/profile/change-password', [App\System\Controller\UserController::class, 'changePassword']);
    Router::post('/profile/upload-avatar', [App\System\Controller\UserController::class, 'uploadAvatar']);

    // 系统菜单路由
    Router::addGroup('/menu', function () {
        Router::get('/list', [App\System\Controller\MenuController::class, 'list']);
        Router::get('/routes', [App\System\Controller\MenuController::class, 'routes']);
        Router::get('/buttons', [App\System\Controller\MenuController::class, 'buttons']);
        Router::get('/{id:\d+}', [App\System\Controller\MenuController::class, 'show']);
        Router::post('', [App\System\Controller\MenuController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\MenuController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\MenuController::class, 'destroy']);
        Router::post('/change-status', [App\System\Controller\MenuController::class, 'changeStatus']);
    });

    // 角色管理路由
    Router::addGroup('/role', function () {
        Router::get('/list', [App\System\Controller\RoleController::class, 'list']);
        Router::get('/all', [App\System\Controller\RoleController::class, 'all']);
        Router::get('/{id:\d+}', [App\System\Controller\RoleController::class, 'show']);
        Router::post('', [App\System\Controller\RoleController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\RoleController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\RoleController::class, 'destroy']);
        Router::post('/change-status', [App\System\Controller\RoleController::class, 'changeStatus']);
    });

    // 用户管理路由
    Router::addGroup('/admin', function () {
        Router::get('/list', [App\System\Controller\AdminController::class, 'list']);
        Router::get('/{id:\d+}', [App\System\Controller\AdminController::class, 'show']);
        Router::post('', [App\System\Controller\AdminController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\AdminController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\AdminController::class, 'destroy']);
        Router::post('/change-status', [App\System\Controller\AdminController::class, 'changeStatus']);
    });

    // ✅ 系统配置管理路由（需要认证和权限）
    Router::addGroup('/config', function () {
        // ✅ 新增：前端表单更新配置接口
        Router::post('/update', [App\System\Controller\ConfigController::class, 'updateConfig']);
        Router::get('/list', [App\System\Controller\ConfigController::class, 'list']);
        Router::get('/type/{type}', [App\System\Controller\ConfigController::class, 'getByType']);
        Router::get('/{id:\d+}', [App\System\Controller\ConfigController::class, 'show']);
        Router::post('', [App\System\Controller\ConfigController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\ConfigController::class, 'update']);
        Router::post('/batch-update', [App\System\Controller\ConfigController::class, 'batchUpdate']);
        Router::delete('/{id:\d+}', [App\System\Controller\ConfigController::class, 'destroy']);
        Router::post('/change-status', [App\System\Controller\ConfigController::class, 'changeStatus']);
    });

    // 通用上传
    Router::post('/upload/image', [App\System\Controller\UploadController::class, 'image']);

    // 业务模块 - 产品（器具）管理路由
    Router::addGroup('/business/product', function () {
        Router::get('/list', [App\System\Controller\Business\ProductController::class, 'list']);
        Router::get('/options', [App\System\Controller\Business\ProductController::class, 'options']);
        Router::get('/{id:\d+}', [App\System\Controller\Business\ProductController::class, 'show']);
        Router::post('', [App\System\Controller\Business\ProductController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\Business\ProductController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\Business\ProductController::class, 'destroy']);
    });

    // 业务模块 - 测试报告路由
    Router::addGroup('/business/test-report', function () {
        Router::get('/by-product/{productId:\d+}', [App\System\Controller\Business\TestReportController::class, 'byProduct']);
        Router::get('/{id:\d+}', [App\System\Controller\Business\TestReportController::class, 'show']);
        Router::post('', [App\System\Controller\Business\TestReportController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\Business\TestReportController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\Business\TestReportController::class, 'destroy']);
    });

    // 业务模块 - 检定证书路由
    Router::addGroup('/business/verification-cert', function () {
        Router::get('/by-product/{productId:\d+}', [App\System\Controller\Business\VerificationCertController::class, 'byProduct']);
        Router::get('/{id:\d+}', [App\System\Controller\Business\VerificationCertController::class, 'show']);
        Router::post('', [App\System\Controller\Business\VerificationCertController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\Business\VerificationCertController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\Business\VerificationCertController::class, 'destroy']);
    });

    // 业务模块 - 校准证书路由
    Router::addGroup('/business/calibration-cert', function () {
        Router::get('/by-product/{productId:\d+}', [App\System\Controller\Business\CalibrationCertController::class, 'byProduct']);
        Router::get('/{id:\d+}', [App\System\Controller\Business\CalibrationCertController::class, 'show']);
        Router::post('', [App\System\Controller\Business\CalibrationCertController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\Business\CalibrationCertController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\Business\CalibrationCertController::class, 'destroy']);
    });

    // 业务模块 - 签名库路由
    Router::addGroup('/business/signature', function () {
        Router::get('/list', [App\System\Controller\Business\SignatureController::class, 'list']);
        Router::get('/all', [App\System\Controller\Business\SignatureController::class, 'all']);
        Router::get('/{id:\d+}', [App\System\Controller\Business\SignatureController::class, 'show']);
        Router::post('', [App\System\Controller\Business\SignatureController::class, 'store']);
        Router::put('/{id:\d+}', [App\System\Controller\Business\SignatureController::class, 'update']);
        Router::delete('/{id:\d+}', [App\System\Controller\Business\SignatureController::class, 'destroy']);
    });

    // 认证（你是谁）在前，鉴权（你能做什么）在后，顺序不可颠倒
}, ['middleware' => [JwtAuthMiddleware::class, PermissionMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});