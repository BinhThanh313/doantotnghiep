<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$testUser = App\Models\User::where('email', 'test@example.com')->first();
$testUserOrders = App\Models\Order::where('user_id', $testUser->id ?? -1)->count();
echo "Test user orders: $testUserOrders\n";

$admin = App\Models\User::where('email', 'admin@electro.vn')->first();
$adminOrders = App\Models\Order::where('user_id', $admin->id ?? -1)->count();
echo "Admin user orders: $adminOrders\n";

$usersWithOrders = App\Models\Order::select('user_id')->distinct()->count();
echo "Users with orders: $usersWithOrders\n";
