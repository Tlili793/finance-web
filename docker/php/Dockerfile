# Dockerfile for Symfony (PHP-FPM)
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    intl \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies (ignoring scripts for now)
RUN composer install --no-interaction --no-scripts --no-autoloader

# Finalize autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data var/

EXPOSE 9000
CMD ["php-fpm"]
