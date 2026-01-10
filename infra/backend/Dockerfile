FROM php:8.3-fpm-alpine

# Dependencias y extensiones necesarias para Laravel + PostgreSQL
RUN apk add --no-cache \
    git \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    libpng-dev \
    postgresql-dev \
    libpq-dev \
 && docker-php-ext-install \
    bcmath \
    pdo_pgsql \
    pgsql \
 && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
