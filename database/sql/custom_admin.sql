-- Tek admin ekle/güncelle: test@gmail.com
INSERT INTO `admins` (`username`, `email`, `password`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
('testtest', 'test@gmail.com', '$2y$10$s8lAuOCmOAz72084OWsmk.GhtPXGIYtUU0e2WrNnNY4dLYIgOMlrK', 'Sistem Yöneticisi', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `username` = VALUES(`username`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `updated_at` = NOW();
