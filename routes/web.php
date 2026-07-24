<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FlashSalePageController;
use App\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index']);

// TẠM THỜI: Route để chạy Seeder thủ công qua trình duyệt
Route::get('/run-seed', function () {
    // Ghi file log tạm
    file_put_contents(storage_path('logs/seeder-log.txt'), "Đang chạy tạo dữ liệu trong NỀN (Background Process)...\n\nVui lòng F5 lại trang /view-seeder-log sau 3-5 phút nữa.\n\n");
    
    try {
        // Xóa sạch dữ liệu các bảng (trừ bảng migrations)
        \Illuminate\Support\Facades\DB::unprepared("
            DO $$ DECLARE
                r RECORD;
            BEGIN
                FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = current_schema() AND tablename != 'migrations') LOOP
                    EXECUTE 'TRUNCATE TABLE ' || quote_ident(r.tablename) || ' CASCADE';
                END LOOP;
            END $$;
        ");
        
        // Chạy lệnh artisan db:seed ngầm hoàn toàn qua CLI
        $artisanPath = base_path('artisan');
        $logPath = storage_path('logs/seeder-log.txt');
        
        $cmd = "php {$artisanPath} db:seed --force >> {$logPath} 2>&1 &";
        exec($cmd);
        
        return "Đã ra lệnh dọn dẹp và chạy ngầm thành công! <br>Quá trình tạo dữ liệu sẽ mất khoảng 3-5 phút (do mạng Supabase chậm). <br>Trình duyệt của bạn sẽ không bị treo nữa. <br><br>Hãy truy cập /view-seeder-log để theo dõi tiến độ.";
    } catch (\Throwable $e) {
        $error = "CÓ LỖI XẢY RA: \n" . $e->getMessage() . "\nLine: " . $e->getLine() . " in " . $e->getFile();
        file_put_contents(storage_path('logs/seeder-log.txt'), $error, FILE_APPEND);
        return $error;
    }
});

Route::get('/test-users', function () {
    return \App\Models\User::all();
});
Route::get('/fix-migrations', function () {
    \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%contact_messages%')->delete();
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return "Đã fix bảng contact_messages!";
});

// Route tự động đăng nhập admin để test
Route::get('/test-admin', function () {
    $admin = \App\Models\User::where('email', 'admin@electro.vn')->first();
    if ($admin) {
        \Illuminate\Support\Facades\Auth::login($admin);
        return redirect('/admin');
    }
    return "Lỗi: Không tìm thấy tài khoản admin@electro.vn trong cơ sở dữ liệu!";
});
// Route xem log để debug
Route::get('/view-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        return nl2br(file_get_contents($logFile));
    }
    return "Không có file log.";
});

Route::get('/view-seeder-log', function () {
    $logFile = storage_path('logs/seeder-log.txt');
    if (file_exists($logFile)) {
        return nl2br(file_get_contents($logFile));
    }
    return "Chưa có log seeder.";
});
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{id}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/vouchers', [ShopController::class, 'vouchers'])->name('shop.vouchers');

Route::get('/contact', fn() => view('contact'))->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware(['auth', 'throttle:5,1'])
    ->name('contact.store');
Route::get('/about', fn() => view('about'))->name('about');
Route::get('/privacy-policy', fn() => view('privacy-policy'))->name('privacy-policy');
Route::get('/terms', fn() => view('terms'))->name('terms');
Route::get('/faq', fn() => view('faq'))->name('faq');
Route::get('/return-policy', fn() => view('return-policy'))->name('return-policy');
Route::get('/bestseller', [ShopController::class, 'bestsellers'])->name('bestseller');
Route::get('/flash-sale', [FlashSalePageController::class, 'index'])->name('flash-sale');
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
    
    // Xóa TOÀN BỘ voucher đang áp dụng (danh sách nhiều mã)
    Route::post('/cart/clear-voucher', function() {
        session()->forget('applied_vouchers');
        return response()->json(['success' => true]);
    })->name('cart.clear-voucher');

    // Checkout
    Route::post('/checkout/select-items', [CheckoutController::class, 'selectItems'])->name('checkout.select-items');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{id}', [CheckoutController::class, 'success'])->name('checkout.success');

    // AJAX: áp dụng / gỡ voucher (hỗ trợ nhiều mã cùng lúc) & tính phí ship (từ checkout blade)
    Route::post('/checkout/apply-voucher', [CheckoutController::class, 'applyVoucher'])->name('checkout.apply-voucher');
    Route::post('/checkout/remove-voucher', [CheckoutController::class, 'removeVoucher'])->name('checkout.remove-voucher');

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