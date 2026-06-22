FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t public