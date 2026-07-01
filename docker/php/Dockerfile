FROM php:8.3-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
    bash \
    $PHPIZE_DEPS \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    linux-headers \
    nodejs \
    npm \
    oniguruma-dev \
    unzip \
    zip

RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/product-balance.ini

RUN addgroup -g ${WWWGROUP} app \
    && adduser -D -G app -u ${WWWUSER} app

USER app

WORKDIR /var/www/html


