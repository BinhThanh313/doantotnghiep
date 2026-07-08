<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // THÊM DÒNG NÀY
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
    }
}