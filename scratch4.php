<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::first();
echo "Order status: " . $order->status . "\n";
$order->update(['status' => 'processing']);
echo "Order status after update: " . $order->status . "\n";
