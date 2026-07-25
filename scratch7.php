<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::first();
echo "Current bulk test order ID: " . $order->id . "\n";

$request = Illuminate\Http\Request::create('/api/admin/orders/bulk', 'POST', [
    'ids' => [$order->id],
    'action' => 'update_status',
    'status' => 'processing'
]);
$request->headers->set('Accept', 'application/json');

$admin = App\Models\User::where('email', 'admin@electro.vn')->first();
$app['auth']->guard('sanctum')->setUser($admin);

$response = $app->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response content: " . $response->getContent() . "\n";
