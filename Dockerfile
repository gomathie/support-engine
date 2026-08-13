FROM php:8.3-fpm

# System deps for PostgreSQL, zip, GD (PDF), Node 22
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libicu-dev \
        unzip git curl ca-certificates gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip gd bcmath pcntl intl \
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
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Node deps (layer cache)
COPY package.json package-lock.json ./
RUN npm ci

# Full source
COPY . .

# Storage directories Laravel expects.
#
# Must come before any artisan command: .dockerignore deliberately excludes the
# host's storage/framework caches (they are machine-local junk), so these do not
# arrive with the source, and package:discover boots the framework — which fails
# with "Please provide a valid cache path" if the view cache directory is absent.
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Finish composer autoload + package discovery
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# Build frontend assets
RUN npm run build

# Ensure OS environment variables are populated in $_ENV so they override .env
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/99-variables-order.ini

EXPOSE 8000

# Default: run the dev server; docker-compose can override for tests
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
