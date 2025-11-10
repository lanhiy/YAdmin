<?php

declare(strict_types=1);

use App\Middleware\JwtAuthMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', [App\Controller\IndexController::class, 'index']);

// ========== 不需要认证的路由 ==========
Router::post('/system/login', [App\System\Controller\UserController::class, 'login']);
//Router::post('/system/register', [App\System\Controller\UserController::class,'register']); // 如果有注册

// ========== 需要认证的路由 ==========
Router::addGroup('/system', function () {
    // 用户相关
    Router::get('/user/info', [App\System\Controller\UserController::class, 'userInfo']);
    Router::post('/user/logout', [App\System\Controller\UserController::class, 'logout']);

    // ✅ 个人中心路由
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

}, ['middleware' => [JwtAuthMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});