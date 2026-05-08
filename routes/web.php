<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', function () { return "Trang danh sách sản phẩm"; })->name('shop.index');
Route::get('/shop/{id}', function ($id) { return "Chi tiết sản phẩm: " . $id; })->name('shop.show');
Route::get('/cart', function () { return "Trang giỏ hàng"; })->name('cart.index');
Route::post('/cart/add', function () { return "Thêm vào giỏ hàng"; })->name('cart.add');
Route::get('/contact', function () { return "Trang liên hệ"; })->name('contact');
Route::get('/profile', function () { return "Trang cá nhân"; })->name('profile');
// Các route xác thực (Tạm thời để tránh lỗi giao diện)
Route::get('/login', function () { return "Trang đăng nhập"; })->name('login');
Route::get('/register', function () { return "Trang đăng ký"; })->name('register');
Route::post('/logout', function () { return "Xử lý đăng xuất"; })->name('logout');

// Các route khác cũng xuất hiện trong file app.blade.php của bạn
Route::get('/profile', function () { return "Trang cá nhân"; })->name('profile');
Route::get('/wishlist', function () { return "Danh sách yêu thích"; })->name('wishlist');
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{id}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/cart', function () {
    return view('shop.cart'); // Nếu bạn lưu trong thư mục shop thì là view('shop.cart')
})->name('cart.index');

Route::get('/checkout', function () {
    return view('shop.checkout'); // Đổi thành 'shop.checkout' nếu bạn lưu trong thư mục shop
})->name('checkout');

Route::get('/bestseller', function () {
    return view('shop.bestseller'); // Đổi thành 'shop.bestseller' nếu bạn để trong thư mục shop
})->name('bestseller');

// routes/web.php — thêm vào cuối
Route::get('/admin/{any?}', function () {
    // Khi đã build (production)
    $indexPath = public_path('admin/index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }
    // Khi dev, redirect sang Vite dev server
    return file_get_contents(public_path('admin/index.html'));
})->where('any', '.*');