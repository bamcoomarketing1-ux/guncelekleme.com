# Railway'e Deploy

Bu proje Railway'de **Docker** ile çalışacak şekilde hazırlandı. Nginx +
PHP-FPM + queue worker aynı container içinde `supervisor` ile yönetilir.
Railway'in atadığı `PORT` otomatik olarak nginx'e uygulanır.

## Eklenen dosyalar

| Dosya | Görevi |
|-------|--------|
| `Dockerfile` | Vite build + PHP 8.3 + nginx + php-fpm imajı |
| `docker/nginx.conf.template` | `${PORT}` üzerinde dinleyen nginx yapılandırması |
| `docker/supervisord.conf` | php-fpm, nginx ve `queue:work`'ü çalıştırır |
| `docker/entrypoint.sh` | migrate + config cache + servisleri başlatır |
| `railway.json` | Railway build/deploy ayarları |
| `.dockerignore` | vendor, node_modules, .env gibi dosyaları hariç tutar |
| `.env.railway.example` | Panelde girilecek değişkenlerin listesi |

## Adım adım kurulum

### 1. Kodu bir Git deposuna gönderin
Railway GitHub deposundan deploy eder.

```bash
git init
git add .
git commit -m "Railway deploy hazirligi"
git branch -M main
git remote add origin https://github.com/KULLANICI/REPO.git
git push -u origin main
```

### 2. Railway'de proje oluşturun
1. https://railway.app → **New Project → Deploy from GitHub repo**
2. Bu depoyu seçin. Railway `Dockerfile`'ı otomatik algılar.

### 3. MySQL ekleyin
Aynı proje içinde: **New → Database → Add MySQL**.

### 4. Değişkenleri girin
Uygulama servisinizde **Variables** sekmesine geçip
`.env.railway.example` içindeki değerleri girin. En kritik olanlar:

- `APP_KEY` → yereldeki `.env` içindeki mevcut anahtarı kopyalayın.
- `DB_CONNECTION=mysql`
- `DB_URL=${{MySQL.MYSQL_URL}}` → MySQL servisine referans (adı farklıysa güncelleyin).
- `APP_ENV=production`, `APP_DEBUG=false`

> `${{MySQL.MYSQL_URL}}` ifadesindeki **MySQL**, veritabanı servisinizin
> adıdır. Railway servise başka bir ad verdiyse onu yazın.

### 5. Deploy & domain
- Deploy otomatik başlar. Bittiğinde **Settings → Networking → Generate Domain**.
- Oluşan adresi `APP_URL` değişkenine yazıp tekrar deploy edin.

## İlk kurulum sonrası (opsiyonel)

Seed/import gerekiyorsa Railway'de servis üzerinde bir komut çalıştırın
(**Deployments → three dots → Run command** veya Railway CLI):

```bash
php artisan db:seed --class=BackupImportSeeder --force
```

## Zamanlanmış görevler (scheduler)

`entrypoint.sh` queue worker'ı çalıştırır ama Laravel scheduler çalıştırmaz.
Gerekiyorsa Railway'de **ayrı bir Cron servisi** ekleyin:

- Start command: `php artisan schedule:run`
- Cron schedule: `* * * * *`

## Notlar

- `route:cache`, closure tabanlı route'lar nedeniyle bilinçli olarak atlanır;
  `config:cache` ve `view:cache` uygulanır.
- Yüklenen dosyalar (`storage/`) container yeniden başlarken silinir. Kalıcılık
  için Railway **Volume** ekleyip `/var/www/html/storage` yoluna bağlayın ya da
  S3 benzeri bir disk kullanın.
- SQLite ile devam etmek isterseniz: `DB_CONNECTION=sqlite`,
  `DB_DATABASE=/var/www/html/database/database.sqlite` ve bir Volume gerekir;
  ancak production için MySQL önerilir.
