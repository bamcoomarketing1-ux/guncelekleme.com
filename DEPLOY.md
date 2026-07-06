# Production Deploy

## Gereksinimler

- PHP 8.2+, Composer
- MySQL 8+
- Nginx veya Apache
- Node.js (opsiyonel, frontend build için)

## Kurulum

```bash
cd alisulasyon-laravel
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

`.env` dosyasında MySQL, SMTP ve `OPENAI_API_KEY` ayarlayın.

```bash
php scripts/setup_db.php
php artisan migrate --force
php artisan db:seed --class=BackupImportSeeder --force
php artisan storage:link
```

Wheel geçmişi tam import (canlı API erişimi varsa):

```bash
php artisan wheel:import-history --api=https://your-api.com/api --token=ADMIN_TOKEN
```

## Nginx örneği

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/alisulasyon-laravel/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

## Cron & Queue

```cron
* * * * * cd /var/www/alisulasyon-laravel && php artisan schedule:run >> /dev/null 2>&1
```

Queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Supervisor ile kalıcı queue worker önerilir.

## Telegram webhook

Admin panelden bot token girin, ardından:

```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=https://your-domain.com/api/telegram/webhook"
```

## Doğrulama

```bash
php artisan serve
python scripts/verify_all_routes.py
php artisan test
```

Admin: `/panel/login` — `test@gmail.com` / `testtest`
