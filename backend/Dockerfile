FROM php:8.3-fpm-alpine

# Instala extensões necessárias para PostgreSQL
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

EXPOSE 9000
