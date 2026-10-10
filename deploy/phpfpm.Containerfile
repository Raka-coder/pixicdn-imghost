FROM docker.io/library/php:8.2-fpm-bookworm
# Extensi wajib rules §2.1: pdo_mysql, mbstring, gd, fileinfo (+opcache)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring gd fileinfo opcache \
    && rm -rf /var/lib/apt/lists/*
COPY php.ini /usr/local/etc/php/conf.d/pixicdn.ini
WORKDIR /srv
EXPOSE 9000
