<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // KHÔNG dùng $middleware->statefulApi() toàn cục — nếu bật cho cả
        // route /admin/*, Sanctum sẽ ưu tiên xác thực qua session 'web'
        // (dùng chung với storefront khách hàng) thay vì Bearer token,
        // khiến admin panel bị ảnh hưởng bởi trạng thái đăng nhập của
        // khách hàng trên storefront (và ngược lại). Thay vào đó, middleware
        // stateful được áp dụng có chọn lọc, chỉ cho các route công khai
        // cần "tùy chọn đăng nhập qua session" (VD: chatbot) — xem routes/api.php.
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();