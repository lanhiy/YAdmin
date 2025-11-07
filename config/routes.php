<?php

declare(strict_types=1);

use App\Middleware\JwtAuthMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

// ========== 不需要认证的路由 ==========
Router::post('/system/login', 'App\System\Controller\UserController@login');
Router::post('/system/register', 'App\System\Controller\UserController@register'); // 如果有注册

// ========== 需要认证的路由 ==========
Router::addGroup('/system', function () {
    // 用户相关
    Router::get('/user/info', 'App\System\Controller\UserController@userInfo');
    Router::post('/user/logout', 'App\System\Controller\UserController@logout');

    // ✅ 个人中心路由
    Router::get('/profile', 'App\System\Controller\UserController@getProfile');
    Router::put('/profile', 'App\System\Controller\UserController@updateProfile');
    Router::post('/profile/change-password', 'App\System\Controller\UserController@changePassword');
    Router::post('/profile/upload-avatar', 'App\System\Controller\UserController@uploadAvatar');

    // 系统菜单路由
    Router::addGroup('/menu', function () {
        Router::get('/list', 'App\System\Controller\MenuController@list');
        Router::get('/routes', 'App\System\Controller\MenuController@routes');
        Router::get('/buttons', 'App\System\Controller\MenuController@buttons');
        Router::get('/{id:\d+}', 'App\System\Controller\MenuController@show');
        Router::post('', 'App\System\Controller\MenuController@store');
        Router::put('/{id:\d+}', 'App\System\Controller\MenuController@update');
        Router::delete('/{id:\d+}', 'App\System\Controller\MenuController@destroy');
        Router::post('/change-status', 'App\System\Controller\MenuController@changeStatus');
    });

    // 角色管理路由
    Router::addGroup('/role', function () {
        Router::get('/list', 'App\System\Controller\RoleController@list');
        Router::get('/all', 'App\System\Controller\RoleController@all');
        Router::get('/{id:\d+}', 'App\System\Controller\RoleController@show');
        Router::post('', 'App\System\Controller\RoleController@store');
        Router::put('/{id:\d+}', 'App\System\Controller\RoleController@update');
        Router::delete('/{id:\d+}', 'App\System\Controller\RoleController@destroy');
        Router::post('/change-status', 'App\System\Controller\RoleController@changeStatus');
    });

    // 用户管理路由
    Router::addGroup('/admin', function () {
        Router::get('/list', 'App\System\Controller\AdminController@list');
        Router::get('/{id:\d+}', 'App\System\Controller\AdminController@show');
        Router::post('', 'App\System\Controller\AdminController@store');
        Router::put('/{id:\d+}', 'App\System\Controller\AdminController@update');
        Router::delete('/{id:\d+}', 'App\System\Controller\AdminController@destroy');
        Router::post('/change-status', 'App\System\Controller\AdminController@changeStatus');
    });

}, ['middleware' => [JwtAuthMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});