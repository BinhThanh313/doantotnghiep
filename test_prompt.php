<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\Chatbot\ChatbotResponseService::class);

$history = [
    ['sender' => 'bot', 'message' => 'Mình tìm thấy 2 sản phẩm phù hợp: - Samsung Galaxy Z Fold5 — 45.990.000đ (Điện thoại) - Samsung Galaxy S24 Ultra — 25.192.000đ (Điện thoại)'],
    ['sender' => 'user', 'message' => 'Trong mấy cái đó thì cái nào chụp ảnh ngon hơn?'],
    ['sender' => 'bot', 'message' => 'Cả hai sản phẩm Samsung Galaxy Z Fold5 và Samsung Galaxy S24 Ultra đều có cấu hình camera sau tương tự...'],
    ['sender' => 'user', 'message' => 'Thế cái đầu tiên pin có trâu không?'],
    ['sender' => 'bot', 'message' => 'Cái đầu tiên bạn nhắc tới là Samsung Galaxy Z Fold5, với pin 5000 mAh và hỗ trợ sạc tối đa 45 W.'],
];

// simulate how findRecentlyMentionedProducts works
$reflection = new \ReflectionClass($service);
$method = $reflection->getMethod('findRecentlyMentionedProducts');
$method->setAccessible(true);
$recentProducts = $method->invoke($service, $history, 'Thế cái thứ hai pin có trâu không?');

$method2 = $reflection->getMethod('buildProductContextWithSpecs');
$method2->setAccessible(true);
$context = $method2->invoke($service, $recentProducts);

echo $context;
