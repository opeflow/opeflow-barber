FROM php:8.4-cli-alpine

RUN apk add --no-cache $PHPIZE_DEPS postgresql-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

WORKDIR /var/www/html
