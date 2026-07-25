<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::orderBy('id', 'desc')->take(10)->get();
foreach ($products as $p) {
    echo $p->name . " -> " . $p->image . "\n";
}
