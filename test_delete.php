<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $product = App\Models\Product::find(377);
    if ($product) {
        $product->delete();
        echo "Deleted successfully";
    } else {
        echo "Product not found";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
