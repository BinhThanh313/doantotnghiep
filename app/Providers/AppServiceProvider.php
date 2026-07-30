<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // THÊM DÒNG NÀY
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model; // THÊM DÒNG NÀY
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Mail::extend('brevo', function (array $config = []) {
            return new BrevoApiTransport($config['key'] ?? env('BREVO_API_KEY'));
        });

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https'); // FIX MIXED CONTENT ON RENDER
            
            // Bắt buộc Laravel dùng đúng tên miền gốc (bỏ qua cổng 10000 của Render)
            if (env('APP_URL')) {
                \Illuminate\Support\Facades\URL::forceRootUrl(env('APP_URL'));
            }
        }
        
        Schema::defaultStringLength(191); // THÊM DÒNG NÀY
        Paginator::useBootstrapFive();

        // Giới hạn tần suất gọi chatbot — 20 tin nhắn/phút cho mỗi
        // user đăng nhập (theo user id) hoặc mỗi khách vãng lai (theo IP).
        // Cần thiết vì mỗi tin nhắn không khớp rule-based sẽ gọi ra LLM
        // ngoài (Groq free tier), spam có thể làm cạn quota rất nhanh.
        RateLimiter::for('chatbot', function ($request) {
            $key = optional($request->user())->id ?? $request->ip();
            return Limit::perMinute(20)->by($key);
        });

        // Giới hạn số lần đăng nhập admin — 5 lần/phút, tính theo cặp
        // (email + IP) để chống brute-force mật khẩu qua /api/admin/login.
        // Trước đây route này KHÔNG có throttle, nên có thể bị dò mật khẩu
        // admin bằng cách gửi liên tục không giới hạn số lần thử.
        RateLimiter::for('login', function ($request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        // Giới hạn chung cho các API công khai không cần đăng nhập
        // (tính phí ship, flash sale hiện tại, xem review sản phẩm...)
        // — chống spam/scrape gây quá tải, theo IP.
        RateLimiter::for('public-api', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Chia sẻ danh mục cho menu trên tất cả các trang
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $view->with('categories', \App\Models\Category::withCount('products')->get());
        });
    }
}