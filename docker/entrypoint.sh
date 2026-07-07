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
chown -R www-data:www-data storage bootstrap/cache public/storage 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache public/storage 2>/dev/null || true

# storage:link — önce sembolik bağ dene, başarısız olursa gerçek dizin kullan
if [ -L public/storage ]; then
  rm -f public/storage
fi
if [ ! -d public/storage ]; then
  mkdir -p public/storage
fi
# storage/app/public altındaki mevcut dosyaları public/storage'a kopyala
if [ -d storage/app/public ]; then
  cp -rn storage/app/public/. public/storage/ 2>/dev/null || true
fi
chown -R www-data:www-data public/storage 2>/dev/null || true

# Veritabanı migrasyonları
echo "[entrypoint] Running migrations..."
php artisan migrate --force || echo "[entrypoint] UYARI: migrate başarısız (DB bağlantısını kontrol edin)."

# Config cache (route:cache closure route'lar nedeniyle atlanır)
php artisan config:clear || true
php artisan config:cache || true
php artisan view:cache || true

echo "[entrypoint] Starting supervisor (php-fpm + nginx + queue)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
