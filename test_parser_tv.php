<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parser = app(App\Services\Chatbot\ProductQueryParser::class);
$filters = $parser->parse('Shop có Tivi LG nào 65 inch giá dưới 40 triệu không?');
print_r($filters);
