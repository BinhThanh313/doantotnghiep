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
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Admin\ProductVariantController;

// ==================== PUBLIC ROUTES ====================

Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/shipping/calculate', [ShippingController::class, 'calculateFee']);
Route::post('/voucher/apply', [CheckoutController::class, 'applyVoucher']);
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// ==================== PAYMENT CALLBACKS (PUBLIC) ====================
// Webhook từ VNPay, MoMo (không cần auth)
Route::get('/payment/vnpay/callback',  [PaymentController::class, 'vnpayCallback']);
Route::post('/payment/vnpay/callback', [PaymentController::class, 'vnpayCallback']);
Route::post('/payment/momo/notify',    [PaymentController::class, 'momoNotify']);
Route::get('/payment/momo/callback',   [PaymentController::class, 'momoCallback']);

// ==================== ADMIN ROUTES ====================

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Orders
    Route::get('/orders/export',          [OrderController::class, 'export']);
    Route::get('/orders/stats/summary',   [OrderController::class, 'stats']);
    Route::get('/orders/report',          [OrderController::class, 'report']);
    Route::post('/orders/bulk',           [OrderController::class, 'bulk']);
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{id}/refund',    [OrderController::class, 'refund']);

    // Products, Categories, Users, Vouchers
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('vouchers', VoucherController::class);
    Route::patch('/vouchers/{id}/toggle', [VoucherController::class, 'toggle']);

    // Product Variants
    Route::get('/products/{productId}/variants',                      [ProductVariantController::class, 'index']);
    Route::post('/products/{productId}/variants',                     [ProductVariantController::class, 'store']);
    Route::put('/products/{productId}/variants/{id}',                 [ProductVariantController::class, 'update']);
    Route::delete('/products/{productId}/variants/{id}',              [ProductVariantController::class, 'destroy']);
    Route::post('/products/{productId}/variants/{id}/adjust-stock',   [ProductVariantController::class, 'adjustStock']);
    Route::get('/products/{productId}/variants/{id}/logs',            [ProductVariantController::class, 'logs']);

    // Shipping Admin
    Route::get('/shipping/carriers',              [ShippingController::class, 'carriers']);
    Route::post('/shipping/carriers',             [ShippingController::class, 'storeCarrier']);
    Route::put('/shipping/carriers/{id}',         [ShippingController::class, 'updateCarrier']);
    Route::get('/shipping/carriers/{id}/zones',   [ShippingController::class, 'zones']);
    Route::post('/shipping/carriers/{id}/zones',  [ShippingController::class, 'storeZone']);
    Route::put('/shipping/zones/{id}',            [ShippingController::class, 'updateZone']);
    Route::delete('/shipping/zones/{id}',         [ShippingController::class, 'destroyZone']);
    Route::get('/shipping/shipments',             [ShippingController::class, 'shipments']);
    Route::put('/shipping/shipments/{id}',        [ShippingController::class, 'updateShipment']);

    // Reviews
    Route::get('/reviews',                           [AdminReviewController::class, 'index']);
    Route::get('/reviews/{id}',                      [AdminReviewController::class, 'show']);
    Route::patch('/reviews/{id}/toggle-visibility',  [AdminReviewController::class, 'toggleVisibility']);
    Route::delete('/reviews/{id}',                   [AdminReviewController::class, 'destroy']);

    // Payments
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index']);
    Route::get('/payments/{id}', [\App\Http\Controllers\Admin\PaymentController::class, 'show']);
    Route::post('/payments/{id}/verify-bank', [\App\Http\Controllers\Admin\PaymentController::class, 'verifyBank']);
    Route::post('/payments/{id}/reject-bank', [\App\Http\Controllers\Admin\PaymentController::class, 'rejectBank']);
    Route::post('/payments/{id}/transition', [\App\Http\Controllers\Admin\PaymentController::class, 'transition']);
});

// ==================== CUSTOMER API ROUTES ====================

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{reviewId}/helpful',   [ReviewController::class, 'helpful']);
    Route::get('/reviews/{reviewId}',            [ReviewController::class, 'show']);
    Route::post('/checkout/shipping-fee',        [CheckoutController::class, 'calculateShipping']);
  
    // ==================== PAYMENT ROUTES (YÊU CẦU ĐĂNG NHẬP) ====================
    Route::post('/payment/create',         [PaymentController::class, 'create']);
    Route::post('/payment/{id}/verify',    [PaymentController::class, 'verify']);
    Route::get('/payment/{id}/status',     [PaymentController::class, 'status']);
    Route::post('/payment/{id}/refund',    [PaymentController::class, 'refund']);
});