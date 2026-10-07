# Container image for local demos and CI smoke tests.
# Serves the API with PHP's built-in server via `php artisan serve`, which keeps
# the image small and simple. For production, run the same code under php-fpm
# behind nginx (or Laravel Octane) instead.
FROM php:8.4-cli-alpine

RUN apk add --no-cache git unzip libzip-dev postgresql-dev icu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip intl bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first so this layer is cached between code changes.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
