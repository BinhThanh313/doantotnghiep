<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = app(App\Services\Chatbot\ProductQueryParser::class);
print_r($p->parse('Shop có điện thoại Samsung nào giá dưới 30 triệu mà RAM 12GB không?'));
