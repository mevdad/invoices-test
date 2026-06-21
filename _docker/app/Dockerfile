# syntax=docker/dockerfile:1

# ---------- Stage 1: PHP dependencies (vendor) ----------
FROM composer:2 AS vendor
WORKDIR /app
# Install on the manifest layer first so it is cached unless deps change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
# Then bring the source and dump an optimized classmap (incl. App\).
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- Stage 2: Nuxt SSR build (.output) ----------
FROM node:22-bookworm AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 3: Runtime ----------
FROM php:8.5-fpm AS runtime

# System libs + PHP extensions. build-essential / *-dev are needed to compile
# the extensions; left in place for simplicity (could be purged to slim further).
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev && \
    docker-php-ext-configure intl && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Node binary only — the Nuxt `.output` bundle is self-contained, so we don't
# need npm or the project node_modules at runtime.
COPY --from=node:22-bookworm /usr/local/bin/node /usr/local/bin/node

WORKDIR /var/www
RUN rm -rf /var/www/html

# Application source (vendor / .output / node_modules excluded via .dockerignore).
COPY . /var/www
# Baked dependencies + built frontend from the build stages.
COPY --from=vendor   /app/vendor  /var/www/vendor
COPY --from=frontend /app/.output /var/www/.output

# Bake the package manifest and prepare writable dirs, so no build/discovery is
# needed at container start. (Falls back to runtime auto-discovery if skipped.)
RUN php artisan package:discover --ansi || true; \
    mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# 9000 = php-fpm (FastCGI), 3000 = Nuxt SSR. Each compose service overrides the
# command (php-fpm / node / scheduler / queue); the default is php-fpm.
EXPOSE 9000 3000
CMD ["php-fpm"]
