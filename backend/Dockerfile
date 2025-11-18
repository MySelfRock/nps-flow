# Multi-stage build for Laravel backend optimized for AWS ECS

# Stage 1: Composer dependencies
FROM composer:2.6 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Stage 2: Production image
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl \
    git \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    mbstring \
    opcache \
    bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy application code
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

# Copy composer dependencies from stage 1
COPY --from=composer --chown=www-data:www-data /app/vendor ./vendor

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev || true

# Create storage and cache directories
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy Docker configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create simple health check
RUN mkdir -p public && echo '<?php echo json_encode(["status" => "ok"]);' > public/health.php

# Optimize Laravel (cache config, routes, views)
RUN php artisan config:cache 2>/dev/null || true \
    && php artisan route:cache 2>/dev/null || true \
    && php artisan view:cache 2>/dev/null || true

# Create startup script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
