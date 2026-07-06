-- Otomatik üretildi: php scripts/build_admin_seed_sql.php
SET NAMES utf8mb4;

INSERT INTO `admins` (`username`, `email`, `password`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
('testtest', 'test@gmail.com', '$2y$10$x0/8TnI2dorhEcKJeJtBxOnm9E/NtGmp6SrOUE6xhaYP07ll1rFg6', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('owner', 'owner@alisulasyon.com', '$2y$10$jvkT7MlP9we3T2us72I1VuZ2uHhj1KRTvqnePXZxiYU/SVA0bzn3y', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('alisulasyon', 'alisulasyon@gmail.com', '$2y$10$DuIhpQfGkWFdtU8AB55BtOWOQ07l20ZrSK/R0XtLsrq6MWHo9dz3i', 'Sistem Yöneticisi', NULL, NOW(), NOW()),
('adminadminadminadminadmin', 'adminadminadminadminadmin@gmail.com', '$2y$10$U84l2hIw6P.QBK9wDX475e8gifiCJX/i6gKVmNi8/jIXXTRj7w29.', 'Sistem Yöneticisi', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`), `password`=VALUES(`password`), `role`=VALUES(`role`), `updated_at`=NOW();
