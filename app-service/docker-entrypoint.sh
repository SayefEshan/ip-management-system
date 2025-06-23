#!/bin/bash

# Wait for services to be ready
sleep 10

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear

# Start services
service nginx start && php-fpm