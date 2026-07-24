#!/bin/bash

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Migrate database (force is required in production)
php artisan migrate --force

# Link storage
php artisan storage:link

# Copy demo images to storage
mkdir -p storage/app/public/products
cp public/img/product-*.png storage/app/public/products/
cp public/img/carousel-*.png storage/app/public/products/ || true
cp public/img/header-img.jpg storage/app/public/products/ || true

# Start Apache in foreground
apache2-foreground
