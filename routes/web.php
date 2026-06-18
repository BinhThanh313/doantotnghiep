<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index']);

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{id}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/vouchers', [ShopController::class, 'vouchers'])->name('shop.vouchers');

Route::get('/contact', fn() => view('contact'))->name('contact');
Route::get('/bestseller', [ShopController::class, 'bestsellers'])->name('bestseller');

Route::post('/checkout/shipping-fee', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping-fee');
Auth::routes();

/*
|--------------------------------------------------------------------------
| AUTH REQUIRED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', function() {
        $orders = \App\Models\Order::where('user_id', Auth::id())
            ->latest()->paginate(10);
        return view('profile', compact('orders'));
    })->name('profile');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    
    // Thêm route xóa voucher ở đây
    Route::post('/cart/clear-voucher', function() {
        session()->forget('applied_voucher');
        return response()->json(['success' => true]);
    })->name('cart.clear-voucher');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{id}', [CheckoutController::class, 'success'])->name('checkout.success');

    // AJAX: apply voucher & calculate shipping (từ checkout blade)
    Route::post('/checkout/apply-voucher', [CheckoutController::class, 'applyVoucher'])->name('checkout.apply-voucher');

    // routes/web.php — thêm vào group middleware(['auth'])
    Route::get('/payment/return', fn() => view('payment-return'))->name('payment.return');
    Route::get('/payment/failed', fn() => view('payment-failed'))->name('payment.failed');
    Route::get('/orders/{id}', [CheckoutController::class, 'orderDetail'])->name('order.detail');
    
});

/*
|--------------------------------------------------------------------------
| ADMIN SPA (Vue Frontend)
|--------------------------------------------------------------------------
*/
Route::get('/admin/{any?}', function () {
    $path = public_path('admin/index.html');
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    abort(404, 'Admin frontend not built. Run: cd admin-frontend && npm run build');
})->where('any', '.*');