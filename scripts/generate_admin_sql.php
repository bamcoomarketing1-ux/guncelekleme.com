<?php

/**
 * Özel admin şifresi için SQL üret.
 * php scripts/generate_admin_sql.php owner@site.com "GüçlüŞifre" --username=owner
 */
$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$username = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--username=')) {
        $username = substr($arg, 11);
    }
}

if (! $email || ! $password) {
    fwrite(STDERR, "Kullanım: php scripts/generate_admin_sql.php EMAIL ŞİFRE [--username=ad]\n");
    exit(1);
}

$username = $username ?: explode('@', $email)[0];
$hash = password_hash($password, PASSWORD_BCRYPT);
$emailEsc = addslashes($email);
$userEsc = addslashes($username);

$sql = <<<SQL
-- Tek admin ekle/güncelle: {$email}
INSERT INTO `admins` (`username`, `email`, `password`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
('{$userEsc}', '{$emailEsc}', '{$hash}', 'Sistem Yöneticisi', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `username` = VALUES(`username`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `updated_at` = NOW();

SQL;

$out = dirname(__DIR__).'/database/sql/custom_admin.sql';
file_put_contents($out, $sql);
echo "Yazıldı: {$out}\n";
echo "Email: {$email}\n";
echo "Şifre: {$password}\n";
