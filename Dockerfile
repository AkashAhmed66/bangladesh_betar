# ---------------------------------------------------------------------------
# Bangladesh Betar Audio Archive — application image
# PHP 8.3 + FPM, Composer deps, and pre-built Vite assets baked in.
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.3-fpm-alpine AS app

# System deps + PHP extensions required by Laravel + MySQL.
# ffmpeg/ffprobe power server-side audio ingestion: technical-metadata
# extraction, loudness (EBU R128), proxy transcoding and waveform peaks.
# espeak-ng + poppler + tesseract (ben/eng) power the offline PDF-to-Speech
# tool: text extraction, OCR fallback for scans, and Bangla/English TTS.
RUN apk add --no-cache \
        git curl libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
        oniguruma-dev icu-dev bash mysql-client nginx supervisor ffmpeg \
        espeak-ng poppler-utils tesseract-ocr tesseract-ocr-data-ben tesseract-ocr-data-eng \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath gd zip exif pcntl intl \
    && rm -rf /var/cache/apk/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Application source + built assets.
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Raise PHP upload limits to match nginx (large audio ingestion — M02).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-betar.ini

# Nginx + Supervisor + entrypoint.
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
