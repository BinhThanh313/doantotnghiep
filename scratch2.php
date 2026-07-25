<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$testUser = App\Models\User::where('email', 'test@example.com')->first();
$service = app(\App\Services\ItemBasedRecommendationService::class);
$recommendations = $service->forUser($testUser, 8);

echo "Recommendations for test user:\n";
foreach ($recommendations as $rec) {
    echo "- " . $rec->name . " (is_bestseller: " . $rec->is_bestseller . ")\n";
}

$admin = App\Models\User::where('email', 'admin@electro.vn')->first();
$adminRecommendations = $service->forUser($admin, 8);

echo "\nRecommendations for admin user:\n";
foreach ($adminRecommendations as $rec) {
    echo "- " . $rec->name . " (is_bestseller: " . $rec->is_bestseller . ")\n";
}
