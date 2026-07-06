# Alisulasyon Laravel — Tam Platform

Laravel 13 + Sanctum + gerçek veritabanı. Frontend `public/`, API `/api/*`.

## Kurulum

```powershell
cd alisulasyon-laravel
START.bat
```

MySQL yoksa otomatik **SQLite** (`database/database.sqlite`).

## Adresler

| | URL |
|--|-----|
| Site | http://127.0.0.1:8000/ |
| Admin | http://127.0.0.1:8000/panel/login |
| API | http://127.0.0.1:8000/api/settings |

## Giriş

| Rol | Email | Şifre |
|-----|-------|-------|
| Admin | test@gmail.com | testtest |
| Kullanıcı | yedekteki email | Test123. |

## Tam modül listesi

### Admin panel
- Dashboard (istatistikler, son kullanıcılar, bekleyen siparişler)
- Kullanıcı CRUD + toplu e-posta doğrulama
- Admin CRUD
- Banners, sliders, sponsors, kategoriler, social-media
- Bonuses, trial-bonuses, promocodes, announcements, special-odds
- Ticket events/requests, ticket participations
- Leagues, teams, raffles, market, market-orders
- Tournaments, wheel prizes, wheel history
- Scratch-card yönetimi
- Telegram bot ayarları
- Support history/stats
- Dosya upload (banner, logo, market görseli vb.)
- Settings upload (logo, favicon, gif)

### Public site
- Settings, banners, sliders, sponsors, bonuses/featured
- Market, raffles, tournaments, news, music, popup
- Daily wheel, leaderboard, ticket-events

### Kullanıcı
- Register, login, logout, forgot-password, change-password
- Account, profile, wallets, avatar upload
- Promocode kullanımı, bildirimler, geçmiş
- Market siparişi, bilet katılımı

### Oyunlar (gerçek bakiye)
- **Mines** — start / reveal / cashout
- **Dice** — play
- **Blackjack** — play / hit / stand / double
- Günlük istatistikler, aktif oyun sorgusu

### Diğer
- Daily wheel spin (günde 1 kez, ağırlıklı ödül)
- Scratch card oyna
- Support mesajlaşma
- Telegram webhook endpoint

## Route doğrulama

```powershell
php scripts/verify_routes.php
```

## Veri

`BackupImportSeeder` → 1132 kullanıcı + tüm CMS + wheel geçmişi (561 spin).
