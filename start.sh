#!/bin/bash

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Migrate database (force is required in production)
php artisan migrate --force

# Link storage (backward-compatible cho ảnh local cũ nếu còn)
php artisan storage:link

# Start Apache in foreground
apache2-foreground
