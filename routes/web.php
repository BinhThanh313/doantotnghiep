<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

/*
|--------------------------------------------------------------------------
| CÁC ROUTE CÔNG KHAI (KHÔNG CẦN ĐĂNG NHẬP)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{id}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/bestseller', function () {
    return view('shop.bestseller');
})->name('bestseller');

// Tạo các route Đăng nhập / Đăng ký / Quên mật khẩu tự động của Laravel
Auth::routes();


/*
|--------------------------------------------------------------------------
| CÁC ROUTE YÊU CẦU ĐĂNG NHẬP (MIDDLEWARE AUTH)
|--------------------------------------------------------------------------
| Người dùng chưa đăng nhập khi truy cập các route này sẽ bị đẩy về trang /login
*/

Route::middleware(['auth'])->group(function () {
    
    // Trang cá nhân & Danh sách yêu thích
    Route::get('/profile', function () { 
        return view('profile'); // Cần đảm bảo bạn đã tạo file resources/views/profile.blade.php
    })->name('profile');
    
    Route::get('/wishlist', function () { 
        return "Danh sách yêu thích"; 
    })->name('wishlist');

    // Giỏ hàng
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    // Thanh toán
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{id}', [CheckoutController::class, 'success'])->name('checkout.success');
    
});


/*
|--------------------------------------------------------------------------
| ROUTE CHO ADMIN (VUE/REACT FRONTEND)
|--------------------------------------------------------------------------
*/
Route::get('/admin/{any?}', function () {
    $indexPath = public_path('admin/index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }
    return file_get_contents(public_path('admin/index.html'));
})->where('any', '.*');