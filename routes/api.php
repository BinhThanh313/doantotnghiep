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
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CheckoutController;

// ==================== PUBLIC ROUTES ====================

// Admin login
Route::post('/admin/login', [AuthController::class, 'login']);

// Public: Shipping fee calculation (dùng trong checkout)
Route::post('/shipping/calculate', [ShippingController::class, 'calculateFee']);

// Public: Apply voucher (AJAX từ checkout page)
Route::post('/voucher/apply', [CheckoutController::class, 'applyVoucher']);

// Public: Reviews của sản phẩm
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// ==================== ADMIN ROUTES (auth:sanctum) ====================

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Products CRUD
    Route::apiResource('products', ProductController::class);

    // Orders CRUD + extra actions
    Route::apiResource('orders', OrderController::class);
    Route::get('/orders/stats/summary', [OrderController::class, 'stats']);

    // Categories CRUD
    Route::apiResource('categories', CategoryController::class);

    // Users CRUD
    Route::apiResource('users', UserController::class);

    // Vouchers CRUD + toggle
    Route::apiResource('vouchers', VoucherController::class);
    Route::patch('/vouchers/{id}/toggle', [VoucherController::class, 'toggle']);

    // Shipping: carriers, zones, shipments
    Route::get('/shipping/carriers',              [ShippingController::class, 'carriers']);
    Route::post('/shipping/carriers',             [ShippingController::class, 'storeCarrier']);
    Route::put('/shipping/carriers/{id}',         [ShippingController::class, 'updateCarrier']);
    Route::get('/shipping/carriers/{id}/zones',   [ShippingController::class, 'zones']);
    Route::post('/shipping/carriers/{id}/zones',  [ShippingController::class, 'storeZone']);
    Route::put('/shipping/zones/{id}',            [ShippingController::class, 'updateZone']);
    Route::delete('/shipping/zones/{id}',         [ShippingController::class, 'destroyZone']);
    Route::get('/shipping/shipments',             [ShippingController::class, 'shipments']);
    Route::put('/shipping/shipments/{id}',        [ShippingController::class, 'updateShipment']);

    // Reviews management (admin)
    Route::get('/reviews',                          [AdminReviewController::class, 'index']);
    Route::get('/reviews/{id}',                     [AdminReviewController::class, 'show']);
    Route::patch('/reviews/{id}/toggle-visibility', [AdminReviewController::class, 'toggleVisibility']);
    Route::delete('/reviews/{id}',                  [AdminReviewController::class, 'destroy']);
});

// ==================== CUSTOMER API ROUTES (auth:sanctum) ====================

Route::middleware('auth:sanctum')->group(function () {

    // Wishlist
    Route::get('/wishlist',               [WishlistController::class, 'index']);
    Route::post('/wishlist/{productId}',  [WishlistController::class, 'toggle']);
    Route::delete('/wishlist/{productId}',[WishlistController::class, 'destroy']);

    // Reviews: submit + helpful
    Route::post('/products/{productId}/reviews',    [ReviewController::class, 'store']);
    Route::post('/reviews/{reviewId}/helpful',      [ReviewController::class, 'helpful']);

    // Checkout: shipping fee & voucher (cần login)
    Route::post('/checkout/shipping-fee', [CheckoutController::class, 'calculateShipping']);
});