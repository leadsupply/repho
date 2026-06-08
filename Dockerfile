FROM dunglas/frankenphp:1.12.4-php8.4 AS base

RUN install-php-extensions \
    bcmath \
    pcntl \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    redis \
    zip \
    gd \
    intl

RUN apt-get update && apt-get install -y --no-install-recommends \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY . /app
WORKDIR /app

# -----------------------------------------------------------
# Build stage: install dependencies and compile frontend assets
# -----------------------------------------------------------
FROM base AS build

RUN cp -n .env.example .env || true \
    && sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/' .env

RUN touch /app/database/database.sqlite \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan key:generate \
    && php artisan migrate --force

RUN npm ci && npm run build && rm -rf node_modules

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /app/docker/entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/app/docker/entrypoint.sh"]




