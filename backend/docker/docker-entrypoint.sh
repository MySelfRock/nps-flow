#!/bin/sh
set -e

echo "NPSFlow Backend - Starting container..."

# Create log directories
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor
mkdir -p /var/log/php-fpm
mkdir -p /var/log/php

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Wait for database to be ready
echo "Waiting for database..."
until nc -z -v -w30 ${DB_HOST:-db} ${DB_PORT:-5432}; do
  echo "Waiting for database connection..."
  sleep 2
done
echo "Database is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis..."
REDIS_HOST_CLEAN=$(echo $REDIS_HOST | cut -d':' -f1)
until nc -z -v -w30 ${REDIS_HOST_CLEAN:-redis} 6379; do
  echo "Waiting for Redis connection..."
  sleep 2
done
echo "Redis is ready!"

# Run migrations on first start (if AUTO_MIGRATE env var is set)
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration failed or already run"
fi

# Clear and cache config (optional, for production)
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing application for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "Starting services..."

# Execute CMD
exec "$@"
