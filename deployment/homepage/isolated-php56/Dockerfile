ARG PHP_IMAGE=php:5.6.40-cli-alpine
FROM ${PHP_IMAGE}
RUN sed -i 's|http://|https://|g' /etc/apk/repositories \
    && docker-php-ext-install pdo_mysql mysqli
RUN apk add --no-cache libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .gd-build-deps libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr --with-freetype-dir=/usr \
    && docker-php-ext-install gd \
    && apk del .gd-build-deps
WORKDIR /var/www/html
