<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CheckoutController;

// ==================== PUBLIC ROUTES ====================

Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculateFee']);
Route::post('/voucher/apply', [CheckoutController::class, 'applyVoucher']);
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// ==================== ADMIN ROUTES ====================

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Orders ──────────────────────────────────────────────
    // !QUAN TRỌNG: Các route có path cụ thể phải đặt TRƯỚC apiResource
    // để tránh bị match nhầm là {order} parameter

    // Export (phải trước apiResource để /orders/export không bị hiểu là show(id='export'))
    Route::get('/orders/export',          [OrderController::class, 'export']);

    // Stats & Report
    Route::get('/orders/stats/summary',   [OrderController::class, 'stats']);
    Route::get('/orders/report',          [OrderController::class, 'report']);

    // Bulk actions
    Route::post('/orders/bulk',           [OrderController::class, 'bulk']);

    // CRUD chuẩn
    Route::apiResource('orders', OrderController::class);

    // Actions trên 1 đơn hàng cụ thể
    Route::post('/orders/{id}/refund',    [OrderController::class, 'refund']);

    // ── Products ────────────────────────────────────────────
    Route::apiResource('products', ProductController::class);

    // ── Categories ──────────────────────────────────────────
    Route::apiResource('categories', CategoryController::class);

    // ── Users ───────────────────────────────────────────────
    Route::apiResource('users', UserController::class);

    // ── Vouchers ────────────────────────────────────────────
    Route::apiResource('vouchers', VoucherController::class);
    Route::patch('/vouchers/{id}/toggle', [VoucherController::class, 'toggle']);

    // ── Shipping ────────────────────────────────────────────
    Route::get('/shipping/carriers',              [ShippingController::class, 'carriers']);
    Route::post('/shipping/carriers',             [ShippingController::class, 'storeCarrier']);
    Route::put('/shipping/carriers/{id}',         [ShippingController::class, 'updateCarrier']);
    Route::get('/shipping/carriers/{id}/zones',   [ShippingController::class, 'zones']);
    Route::post('/shipping/carriers/{id}/zones',  [ShippingController::class, 'storeZone']);
    Route::put('/shipping/zones/{id}',            [ShippingController::class, 'updateZone']);
    Route::delete('/shipping/zones/{id}',         [ShippingController::class, 'destroyZone']);
    Route::get('/shipping/shipments',             [ShippingController::class, 'shipments']);
    Route::put('/shipping/shipments/{id}',        [ShippingController::class, 'updateShipment']);

    // ── Reviews ─────────────────────────────────────────────
    Route::get('/reviews',                           [AdminReviewController::class, 'index']);
    Route::get('/reviews/{id}',                      [AdminReviewController::class, 'show']);
    Route::patch('/reviews/{id}/toggle-visibility',  [AdminReviewController::class, 'toggleVisibility']);
    Route::delete('/reviews/{id}',                   [AdminReviewController::class, 'destroy']);
});

// ==================== CUSTOMER API ROUTES ====================

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{reviewId}/helpful',   [ReviewController::class, 'helpful']);
    Route::get('/reviews/{reviewId}',            [ReviewController::class, 'show']);
    Route::post('/checkout/shipping-fee',        [CheckoutController::class, 'calculateShipping']);
});