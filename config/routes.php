<?php

declare(strict_types=1);

use App\Middleware\JwtAuthMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

// ========== 不需要认证的路由 ==========
Router::post('/system/login', 'App\System\Controller\HomeController@login');
Router::post('/system/register', 'App\System\Controller\HomeController@register'); // 如果有注册

// ========== 需要认证的路由 ==========
Router::addGroup('/system', function () {
    // 用户相关
    Router::get('/user/info', 'App\System\Controller\HomeController@userInfo');
    Router::post('/user/logout', 'App\System\Controller\HomeController@logout');
    Router::put('/user/profile', 'App\System\Controller\HomeController@updateProfile');

    // 管理员相关
    Router::get('/admin/list', 'App\System\Controller\AdminController@list');
    Router::post('/admin/create', 'App\System\Controller\AdminController@create');

    // 系统菜单路由
    Router::addGroup('/menu', function () {
        // 获取菜单列表（树形）
        Router::get('/list', 'App\System\Controller\MenuController@list');
        // 获取当前用户的路由菜单
        Router::get('/routes', 'App\System\Controller\MenuController@routes');
        // 获取菜单详情
        Router::get('/{id:\d+}', 'App\System\Controller\MenuController@show');
        // 新增菜单
        Router::post('', 'App\System\Controller\MenuController@store');
        // 更新菜单
        Router::put('/{id:\d+}', 'App\System\Controller\MenuController@update');
        // 删除菜单
        Router::delete('/{id:\d+}', 'App\System\Controller\MenuController@destroy');
        // 修改菜单状态
        Router::post('/change-status', 'App\System\Controller\MenuController@changeStatus');
    });


}, ['middleware' => [JwtAuthMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});