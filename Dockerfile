# Stage 1: Build assets (Node.js & npm)
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Runtime PHP & Nginx
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install extension pendukung (PostgreSQL, zip, gd, ICU, dll) dan Supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-client \
    libpq-dev \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pgsql zip gd bcmath intl opcache pcntl \
    && mkdir -p /var/log/supervisor

# Salin konfigurasi Nginx & Supervisor
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx-site.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Salin file project dari host ke container
COPY . .

# Salin aset yang sudah dibuild dari Stage 1
COPY --from=assets-builder /app/public/build ./public/build

# Install Composer dependencies
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions untuk storage & bootstrap/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Salin dan siapkan entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
