<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cld = new Cloudinary\Cloudinary([
    'cloud' => [
        'cloud_name' => config('services.cloudinary.cloud_name'),
        'api_key'    => config('services.cloudinary.api_key'),
        'api_secret' => config('services.cloudinary.api_secret'),
    ],
]);

try {
    $result = $cld->adminApi()->assets(['max_results' => 10]);
    echo "Total assets found: " . count($result['resources']) . "\n";
    foreach ($result['resources'] as $r) {
        echo $r['secure_url'] . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
