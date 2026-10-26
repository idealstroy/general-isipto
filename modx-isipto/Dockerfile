FROM php:7.4-apache

RUN a2enmod rewrite \
    && sed -i '/debian-security/d' /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install mysqli pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod substitute headers

WORKDIR /var/www/html
