<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\Chatbot\ChatbotResponseService::class);
$res = $service->respond('Shop có điện thoại Samsung nào giá dưới 30 triệu mà RAM 12GB không?', null, []);
print_r($res);
