<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuthController;

// Login không cần auth
Route::post('/admin/login', [AuthController::class, 'login']);

// Tất cả route admin cần đăng nhập
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('products',   ProductController::class);
    Route::apiResource('orders',     OrderController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('users',      UserController::class);
});