<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('recommendation:build-similarity')->daily();

Artisan::command('fix:orders', function () {
    $this->info('Đang sửa dữ liệu đơn hàng rác...');
    
    // Lấy danh sách ID người dùng (khác admin)
    $userIds = \App\Models\User::where('email', '!=', 'admin@electro.vn')->pluck('id')->toArray();
    
    if (empty($userIds)) {
         $this->error('Không có người dùng thật nào trong database!');
         return;
    }
    
    $orders = \App\Models\Order::whereNull('user_id')->get();
    
    $count = 0;
    foreach ($orders as $order) {
        $order->user_id = $userIds[array_rand($userIds)];
        $order->save();
        $count++;
    }
    
    $this->info("Đã cập nhật $count đơn hàng thành công!");
});
