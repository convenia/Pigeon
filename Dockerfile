FROM php:8.1-fpm-alpine

RUN addgroup -S app -g 1000 && adduser -S app -G app -u 1000

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk --no-cache --update add $PHPIZE_DEPS \
libmemcached-dev zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd sockets fileinfo dom xml simplexml

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN pecl install xdebug-3.1.2
RUN docker-php-ext-enable xdebug
