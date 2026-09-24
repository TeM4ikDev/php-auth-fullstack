FROM php:8.5-fpm
LABEL authors="Artem"

RUN apt-get update \
    && apt-get install -y autoconf gcc make libssl-dev pkg-config unzip git curl \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN docker-php-ext-install sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader

COPY src ./src
COPY config ./config
COPY routes ./routes
COPY public ./public_html
COPY consume.php ./

RUN composer dump-autoload --no-dev --optimize

WORKDIR /var/www/public_html

CMD ["php-fpm"]
