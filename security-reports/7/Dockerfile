FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
