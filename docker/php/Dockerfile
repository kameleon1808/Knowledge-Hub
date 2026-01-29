FROM php:8.2-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        git \
        curl \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        zip \
        unzip \
    && docker-php-ext-install pdo_pgsql zip intl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
