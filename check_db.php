<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Product::where('name', 'LIKE', '%iPad Air%')->first();
echo "iPad Air M1 image: " . ($p ? $p->image : 'not found') . "\n";
echo "img_url: " . img_url($p->image) . "\n";
