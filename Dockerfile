FROM php:8.4-fpm

WORKDIR /var/www

# System dependencies + nginx
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    zip \
    nginx \
    gettext-base \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_pgsql pgsql pdo_sqlite mbstring exif pcntl bcmath gd zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js untuk build asset Vite
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Dependency PHP (layer cache terpisah)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Dependency JS (layer cache terpisah)
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Source code
COPY . .

# Autoloader production + build asset + permission storage
RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && mkdir -p \
        storage/app/public \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Konfigurasi nginx untuk Render
COPY docker/nginx.render.conf.template /etc/nginx/templates/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default

EXPOSE 10000

CMD ["/usr/local/bin/entrypoint.sh"]
