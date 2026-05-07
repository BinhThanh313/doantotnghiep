<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',      // ← thêm dòng này nếu chưa có
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Thêm Sanctum cho API
        $middleware->statefulApi();             // ← dòng này thay cho Kernel.php
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();