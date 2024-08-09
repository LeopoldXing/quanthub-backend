FROM php:8.4-rc-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    libcurl4-openssl-dev \
    libsodium-dev

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sodium

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN chown -R www-data:www-data /app

RUN composer clear-cache
RUN composer install --ignore-platform-reqs --no-interaction --optimize-autoloader --no-dev

RUN php -v

EXPOSE 8000
