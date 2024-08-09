FROM php:8.3-alpine3.20

RUN apk update && apk add --no-cache \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    curl-dev \
    libsodium-dev

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sodium

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --ignore-platform-reqs --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /app

RUN composer clear-cache

EXPOSE 8000

CMD ["php-fpm"]
