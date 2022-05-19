FROM php:7.4-fpm-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk --no-cache --update add libmemcached-dev zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install -j$(nproc) gd sockets fileinfo dom tokenizer xml simplexml

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
