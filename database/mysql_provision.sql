-- Sunucuda MySQL kurulumu (root ile çalıştırın)
CREATE DATABASE IF NOT EXISTS alisulasyon
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'alisulasyon'@'localhost' IDENTIFIED BY 'GÜÇLÜ_ŞİFRE_BURAYA';
GRANT ALL PRIVILEGES ON alisulasyon.* TO 'alisulasyon'@'localhost';
FLUSH PRIVILEGES;

-- Sonraki adımlar:
-- 1) php artisan migrate --force
-- 2) mysql -u root -p alisulasyon < database/sql/seed_minimum.sql   (admin + site_settings)
-- 3) php artisan platform:install
