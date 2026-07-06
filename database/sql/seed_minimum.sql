-- =============================================================================
-- Alisulasyon — Admin + site ayarları (migration sonrası import)
-- phpMyAdmin veya: mysql -u root -p alisulasyon < database/sql/seed_minimum.sql
-- Panel: /panel/login
-- =============================================================================
SET NAMES utf8mb4;

INSERT INTO `admins` (`username`, `email`, `password`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
('testtest', 'test@gmail.com', '$2y$10$x0/8TnI2dorhEcKJeJtBxOnm9E/NtGmp6SrOUE6xhaYP07ll1rFg6', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('owner', 'owner@alisulasyon.com', '$2y$10$jvkT7MlP9we3T2us72I1VuZ2uHhj1KRTvqnePXZxiYU/SVA0bzn3y', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('alisulasyon', 'alisulasyon@gmail.com', '$2y$10$DuIhpQfGkWFdtU8AB55BtOWOQ07l20ZrSK/R0XtLsrq6MWHo9dz3i', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('adminadminadminadminadmin', 'adminadminadminadminadmin@gmail.com', '$2y$10$U84l2hIw6P.QBK9wDX475e8gifiCJX/i6gKVmNi8/jIXXTRj7w29.', 'Sistem Yöneticisi', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`), `password`=VALUES(`password`), `role`=VALUES(`role`), `updated_at`=NOW();


INSERT INTO `site_settings` (`id`, `data`, `created_at`, `updated_at`) VALUES
(
  1,
  '{"site_name":"Alisulasyon","primary_color":"#ff0000","index_primary_color":"#3df5e9","active_theme":"default","maintenance_mode":false,"xp_system_enabled":false,"require_email_verification":true,"telegram_bot_username":"alisulasyonresmibot","slider_layout":"single","sponsor_border_effect":true,"sponsor_card_style":"detailed","background_type":"image","chat_enabled":false,"chat_bot_name":"Nexu Bot","quick_access_style":"design1","register_terms_text":"18 yaşından büyük olduğumu onaylıyorum."}',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `updated_at` = NOW();

-- Giriş bilgileri:
-- test@gmail.com / testtest
-- owner@alisulasyon.com / Admin2026!Secure
-- alisulasyon@gmail.com / adminadminadminadminadmin
