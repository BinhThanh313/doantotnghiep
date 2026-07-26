<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Storage::disk('public')->delete(null);
    echo "Deleted null successfully";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
} catch (\TypeError $e) {
    echo "TypeError: " . $e->getMessage();
}
