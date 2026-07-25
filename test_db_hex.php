<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::whereNotNull('image')->take(5)->get();
foreach ($products as $p) {
    echo $p->id . " -> URL: \"" . $p->image . "\" | HEX: " . bin2hex($p->image) . "\n";
}
