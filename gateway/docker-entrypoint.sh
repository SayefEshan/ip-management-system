#!/bin/bash

# Create .env from .env.example if .env doesn't exist
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
    echo "✅ Created .env from .env.example"
fi

# Ensure composer dependencies are installed
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "🔄 Installing composer dependencies..."
    composer install --optimize-autoloader --no-dev --no-interaction
    echo "✅ Composer dependencies installed"
fi

# Set proper permissions
chown -R www-data:www-data /var/www
chmod -R 755 /var/www/storage
chmod -R 755 /var/www/bootstrap/cache

# Wait for dependent services to be ready
echo "⏳ Waiting for dependent services..."
sleep 10

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear

echo "🚀 Starting services..."
# Start services
service nginx start && php-fpm
