<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userIds = App\Models\Order::select('user_id')->distinct()->limit(10)->pluck('user_id');
$users = App\Models\User::whereIn('id', $userIds)->get();
foreach ($users as $u) {
    echo $u->email . "\n";
}
