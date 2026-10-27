ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

# Install build dependencies dan libraries yang dibutuhkan
RUN apk update && apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    bash \
    $PHPIZE_DEPS

# Konfigurasi dan install PHP extensions (GD, PDO MySQL, Zip, Opcache)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        opcache

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Salin custom konfigurasi php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
