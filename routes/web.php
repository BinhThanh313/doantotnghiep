<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

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