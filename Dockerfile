FROM php:8.3-fpm-alpine

RUN addgroup -S app -g 1000 && adduser -S app -G app -u 1000

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk --no-cache --update add $PHPIZE_DEPS \
libmemcached-dev zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install -j$(nproc) gd fileinfo dom xml simplexml pcntl

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions sockets xdebug

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
