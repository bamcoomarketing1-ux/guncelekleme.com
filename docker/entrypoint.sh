#!/usr/bin/env bash
set -e

cd /var/www/html

# Railway PORT değişkenini nginx conf'una yaz
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default
echo "[entrypoint] nginx will listen on port ${PORT}"

# APP_KEY yoksa uyar (Railway'de APP_KEY değişkeni ayarlanmalı)
if [ -z "${APP_KEY}" ]; then
  echo "[entrypoint] UYARI: APP_KEY tanımlı değil. Railway Variables'a APP_KEY ekleyin."
fi

# Depolama izinleri
chown -R www-data:www-data storage bootstrap/cache || true

# storage:link (varsa hata verme)
php artisan storage:link 2>/dev/null || true

# Veritabanı migrasyonları
echo "[entrypoint] Running migrations..."
php artisan migrate --force || echo "[entrypoint] UYARI: migrate başarısız (DB bağlantısını kontrol edin)."

# Config cache (route:cache closure route'lar nedeniyle atlanır)
php artisan config:clear || true
php artisan config:cache || true
php artisan view:cache || true

echo "[entrypoint] Starting supervisor (php-fpm + nginx + queue)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
