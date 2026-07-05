FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        curl \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        sqlite-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        bcmath \
        zip \
        gd \
        intl \
        opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && php artisan config:cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# ---- Local development target (keeps dev deps, no config cache baked in) ----
FROM base AS development
USER root
RUN composer install --optimize-autoloader --no-interaction --no-progress
USER www-data
