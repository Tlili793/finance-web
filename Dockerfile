# STAGE 1: Build Frontend Assets
FROM node:20-alpine AS asset-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --silent
COPY assets/ ./assets/
COPY webpack.config.js ./
RUN npm run build
# STAGE 2: Production Environment
FROM php:8.3-fpm
# Install System Dependencies & Python for FastAPI
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor python3 python3-pip python3-venv git curl unzip \
    libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev gettext-base \
    && rm -rf /var/lib/apt/lists/*
# Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql intl gd zip opcache mbstring
# Configure PHP Production Settings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini "$PHP_INI_DIR/conf.d/opcache.ini"
# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=asset-builder /app/public/build ./public/build
# Install Symfony Dependencies
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
# Setup FastAPI Virtual Environment
RUN python3 -m venv /opt/fastapi-venv \
    && /opt/fastapi-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/fastapi-venv/bin/pip install --no-cache-dir -r api/requirements.txt
# Permissions & Cleanup
RUN mkdir -p var/cache var/log config/jwt \
    && chown -R www-data:www-data var/ \
    && chmod -R 775 var/ \
    && rm -f /etc/nginx/sites-enabled/default
# Config Copies
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
# Render binds to $PORT (default 10000)
EXPOSE 80
CMD ["/entrypoint.sh"]
