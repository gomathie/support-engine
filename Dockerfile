FROM php:8.3-fpm

# System deps for PostgreSQL, zip, GD (PDF), Node 22
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        unzip git curl ca-certificates gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip gd bcmath pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node 22 LTS via NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP deps first (layer cache)
COPY platform/composer.json platform/composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Node deps (layer cache)
COPY platform/package.json platform/package-lock.json ./
RUN npm ci

# Full source
COPY platform/ .

# Finish composer autoload + package discovery
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# Build frontend assets
RUN npm run build

# Storage directories Laravel expects
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

# Default: run the dev server; docker-compose can override for tests
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
