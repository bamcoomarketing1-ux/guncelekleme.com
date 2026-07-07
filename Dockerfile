# ---------- Stage 1: Frontend assets (Vite + Tailwind) ----------
FROM node:20-alpine AS assets
WORKDIR /app

# Sadece bağımlılık dosyalarını kopyalayıp cache'i verimli kullan
COPY package.json package-lock.json ./
RUN npm ci

# Kaynakları kopyala ve production build al
COPY . .
RUN npm run build


# ---------- Stage 2: PHP runtime (nginx + php-fpm + supervisor) ----------
FROM php:8.3-fpm AS app

# Sistem paketleri
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        gettext-base \
        git \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
        libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentileri (mlocati installer ile güvenilir kurulum)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        exif \
        pcntl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Önce composer dosyaları -> bağımlılık cache'i
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# Uygulama kaynak kodu
COPY . .

# Vite build çıktısını assets stage'inden al
COPY --from=assets /app/public/build ./public/build

# Autoload'u tamamla ve izinleri ayarla
RUN composer dump-autoload --optimize --no-dev --no-interaction && \
    mkdir -p storage/app/public storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache && \
    mkdir -p public/storage && \
    chown -R www-data:www-data storage bootstrap/cache public/storage && \
    chmod -R ug+rwx storage bootstrap/cache public/storage

# nginx + supervisor + entrypoint yapılandırmaları
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Railway PORT'u runtime'da atar; entrypoint nginx conf'una yazar
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
