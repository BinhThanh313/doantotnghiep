<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\Order::first();

// Simulate request
$request = Illuminate\Http\Request::create('/api/admin/orders/' . $order->id, 'PUT', [
    'status' => 'delivered'
]);
$request->headers->set('Accept', 'application/json');
// Mock the admin user
$admin = App\Models\User::where('email', 'admin@electro.vn')->first();
$app['auth']->guard('sanctum')->setUser($admin);

$response = $app->handle($request);
echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response content: " . $response->getContent() . "\n";
