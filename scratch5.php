<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    App\Models\AppNotification::send(
        1,
        'test',
        'title',
        'message',
        1,
        'order'
    );
    echo "AppNotification sent successfully.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
