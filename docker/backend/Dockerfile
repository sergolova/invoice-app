FROM php:8.3-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    shadow \
    icu-dev \
    postgresql-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath gd

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupmod -g ${WWWGROUP} www-data 2>/dev/null || groupadd -g ${WWWGROUP} www-data || true \
    && usermod -u ${WWWUSER} -g ${WWWGROUP} www-data 2>/dev/null || true

WORKDIR /var/www/backend

COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
# Start the built-in Laravel server on port 8000 for local development
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]