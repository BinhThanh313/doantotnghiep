#!/bin/bash

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Migrate database (force is required in production)
php artisan migrate --force

# Tự động chạy Seeder tạo dữ liệu mẫu
php artisan db:seed --force

# Link storage
php artisan storage:link

# Start Apache in foreground
apache2-foreground
