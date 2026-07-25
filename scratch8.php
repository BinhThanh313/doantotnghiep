<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::create([
    'customer_name'   => 'Test User',
    'customer_email'  => 'test@example.com',
    'customer_phone'  => '0123456789',
    'province'        => 'Test',
    'address'         => 'Test',
    'total_amount'    => 100000,
    'shipping_fee'    => 0,
    'discount_amount' => 0,
    'payment_method'  => 'cod',
    'status'          => 'pending',
    'payment_status'  => 'unpaid',
    'tracking_number' => App\Models\Order::generateTrackingNumber(),
]);
echo "Created order ID: " . $order->id . "\n";

$request = Illuminate\Http\Request::create('/api/admin/orders/' . $order->id, 'PUT', [
    'status' => 'processing'
]);
$request->headers->set('Accept', 'application/json');

$admin = App\Models\User::where('email', 'admin@electro.vn')->first();
$app['auth']->guard('sanctum')->setUser($admin);

$response = $app->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response content: " . $response->getContent() . "\n";
