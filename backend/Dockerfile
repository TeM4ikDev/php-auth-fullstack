FROM php:8.5-fpm
LABEL authors="Artem"

RUN apt-get update \
    && apt-get install -y curl git libpq-dev unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader

COPY src ./src
COPY config ./config
COPY routes ./routes
COPY database ./database
COPY public ./public_html
COPY migrate.php ./

RUN composer dump-autoload --no-dev --optimize

WORKDIR /var/www/public_html

CMD ["php-fpm"]
