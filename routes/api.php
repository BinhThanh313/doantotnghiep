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
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\InsightController;
use App\Http\Controllers\Admin\ComboController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Api\FlashSaleController as PublicFlashSaleController;
use App\Http\Controllers\Api\ChatbotController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// ==================== PUBLIC ROUTES ====================

// throttle:login — chống brute-force mật khẩu (xem RateLimiter 'login' trong AppServiceProvider)
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:login');

// throttle:public-api — chống spam/scrape các endpoint công khai không cần đăng nhập
Route::middleware('throttle:public-api')->group(function () {
    Route::post('/shipping/calculate', [ShippingController::class, 'calculateFee']);
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
    Route::get('/flash-sales/current', [PublicFlashSaleController::class, 'current']);
});

// Chatbot — công khai, không bắt buộc đăng nhập (khách vãng lai vẫn hỏi
// được về sản phẩm/giá; tra cứu đơn hàng thì cần đăng nhập). Bọc riêng
// bằng EnsureFrontendRequestsAreStateful ở ĐÚNG group này thay vì bật
// statefulApi() toàn cục — để admin panel (xác thực bằng Bearer token
// thuần) không bị Sanctum ưu tiên nhầm sang session 'web' của storefront.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'throttle:chatbot'])->group(function () {
    Route::post('/chatbot/message', [ChatbotController::class, 'handle']);
    Route::get('/chatbot/history/{sessionToken}', [ChatbotController::class, 'history']);
});

// ==================== ADMIN ROUTES ====================

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/profile',          [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/export', [DashboardController::class, 'export']);

    // Admin Insights (gợi ý hành động: restock, bán chậm, xu hướng, combo, giỏ hàng bỏ quên, ...)
    Route::get('/insights', [InsightController::class, 'index']);

    // Combo sản phẩm (tạo từ gợi ý ở trang Insights, hiển thị ở trang chi tiết sản phẩm)
    Route::get('/combos',              [ComboController::class, 'index']);
    Route::post('/combos',             [ComboController::class, 'store']);
    Route::patch('/combos/{id}/toggle', [ComboController::class, 'toggle']);
    Route::delete('/combos/{id}',      [ComboController::class, 'destroy']);

    // Orders
    Route::get('/orders/export',          [OrderController::class, 'export']);
    Route::get('/orders/stats/summary',   [OrderController::class, 'stats']);
    Route::get('/orders/report',          [OrderController::class, 'report']);
    Route::post('/orders/bulk',           [OrderController::class, 'bulk']);
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{id}/refund',    [OrderController::class, 'refund']);

    // Products, Categories, Users, Vouchers
    Route::post('/products/import', [ProductController::class, 'import']);
    Route::patch('/products/{id}/toggle-bestseller', [ProductController::class, 'toggleBestseller']);
    Route::get('/products/{id}/specifications', [ProductController::class, 'getSpecifications']);
    Route::put('/products/{id}/specifications', [ProductController::class, 'updateSpecifications']);
    Route::post('/products/{id}/specifications/regenerate', [ProductController::class, 'regenerateSpecifications']);
    Route::apiResource('products', ProductController::class);

    // Gallery ảnh sản phẩm — thêm/sửa/xoá/sắp xếp TỪNG ảnh một (khác với
    // /products/import vốn chỉ nạp ảnh hàng loạt từ file Excel/CSV).
    Route::get('/products/{productId}/images',            [ProductImageController::class, 'index']);
    Route::post('/products/{productId}/images',            [ProductImageController::class, 'store']);
    Route::post('/products/{productId}/images/{imageId}',  [ProductImageController::class, 'update']); // dùng _method=PUT khi thay file ảnh
    Route::put('/products/{productId}/images/{imageId}',   [ProductImageController::class, 'update']);
    Route::delete('/products/{productId}/images/{imageId}', [ProductImageController::class, 'destroy']);
    Route::patch('/products/{productId}/images/reorder',    [ProductImageController::class, 'reorder']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('users', UserController::class);
    Route::post('/vouchers/import', [VoucherController::class, 'import']);
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

    Route::get('/contact-messages',                  [ContactMessageController::class, 'index']);
    Route::get('/contact-messages/{id}',              [ContactMessageController::class, 'show']);
    Route::patch('/contact-messages/{id}/toggle-read', [ContactMessageController::class, 'toggleRead']);
    Route::delete('/contact-messages/{id}',            [ContactMessageController::class, 'destroy']);
    // Payments
    Route::get('/payments',                   [\App\Http\Controllers\Admin\PaymentController::class, 'index']);
    Route::get('/payments/{id}',              [\App\Http\Controllers\Admin\PaymentController::class, 'show']);
    Route::post('/payments/{id}/verify-bank', [\App\Http\Controllers\Admin\PaymentController::class, 'verifyBank']);
    Route::post('/payments/{id}/reject-bank', [\App\Http\Controllers\Admin\PaymentController::class, 'rejectBank']);
    Route::post('/payments/{id}/transition',  [\App\Http\Controllers\Admin\PaymentController::class, 'transition']);

     // Flash Sales
    Route::get('/flash-sales',                            [FlashSaleController::class, 'index']);
    Route::post('/flash-sales',                           [FlashSaleController::class, 'store']);
    Route::get('/flash-sales/{id}',                       [FlashSaleController::class, 'show']);
    Route::put('/flash-sales/{id}',                       [FlashSaleController::class, 'update']);
    Route::delete('/flash-sales/{id}',                    [FlashSaleController::class, 'destroy']);
    Route::post('/flash-sales/{id}/items',                [FlashSaleController::class, 'addItem']);
    Route::post('/flash-sales/{id}/items/import',          [FlashSaleController::class, 'importItems']);
    Route::put('/flash-sales/{saleId}/items/{itemId}',    [FlashSaleController::class, 'updateItem']);
    Route::delete('/flash-sales/{saleId}/items/{itemId}', [FlashSaleController::class, 'removeItem']);
    Route::get('/flash-sales/{id}/available-products',    [FlashSaleController::class, 'availableProducts']);
});

// ==================== CUSTOMER API ROUTES ====================

// Nhóm này dùng session 'web' của storefront (khách hàng không có Bearer
// token, chỉ đăng nhập qua session) — cần EnsureFrontendRequestsAreStateful
// để Sanctum chấp nhận session làm phương thức xác thực hợp lệ ở đây.
Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    Route::post('/reviews/{reviewId}/helpful',   [ReviewController::class, 'helpful']);
    Route::get('/reviews/{reviewId}',            [ReviewController::class, 'show']);

    // Payment (COD & Bank only)
    Route::post('/payment/create',      [PaymentController::class, 'create']);
    Route::get('/payment/{id}/status',  [PaymentController::class, 'status']);
    Route::post('/payment/{id}/refund', [PaymentController::class, 'refund']);
});