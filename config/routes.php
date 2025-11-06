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
    // ... 更多需要认证的路由

}, ['middleware' => [JwtAuthMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});