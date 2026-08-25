# Use PHP 8.2 FPM Alpine base image
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    nodejs \
    npm \
    autoconf \
    gcc \
    musl-dev \
    make

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl gd bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Copy .env.docker as .env (after COPY . . so it's not overwritten by local .env)
COPY .env.docker .env

# Install PHP dependencies.
#
# Dev dependencies (phpunit, faker) are installed ON PURPOSE. docker-compose.yml
# mounts a named volume over /var/www/vendor and seeds it from THIS layer, so the
# vendor/ built here is the one the running containers actually use. Under
# --no-dev, `docker compose exec app php artisan test` dies on a missing PHPUnit.
RUN composer install --optimize-autoloader

# Generate application key
RUN php artisan key:generate

# Install Node.js dependencies and build assets
RUN npm ci --legacy-peer-deps && npm run build

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# NOTE: Database migrations + seeding run at CONTAINER STARTUP via
# docker-entrypoint.sh (below), NOT during `docker build` — MySQL only exists
# at runtime. Do NOT add a `RUN php artisan migrate` here; it would fail the build.
COPY --chmod=0755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
