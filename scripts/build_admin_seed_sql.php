<?php
/** Tüm admin hashlerini doğrula ve seed SQL güncelle */
$admins = [
    ['testtest', 'test@gmail.com', 'testtest'],
    ['owner', 'owner@alisulasyon.com', 'Admin2026!Secure'],
    ['alisulasyon', 'alisulasyon@gmail.com', 'adminadminadminadminadmin'],
    ['adminadminadminadminadmin', 'adminadminadminadminadmin@gmail.com', 'adminadminadminadminadmin'],
];

$lines = [];
foreach ($admins as [$user, $email, $pass]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    if (! password_verify($pass, $hash)) {
        fwrite(STDERR, "HASH FAIL $email\n");
        exit(1);
    }
    $lines[] = sprintf(
        "('%s', '%s', '%s', 'Sistem Yöneticisi', NULL, NOW(), NOW())",
        addslashes($user),
        addslashes($email),
        $hash
    );
}

$sql = <<<HDR
-- Otomatik üretildi: php scripts/build_admin_seed_sql.php
SET NAMES utf8mb4;

INSERT INTO `admins` (`username`, `email`, `password`, `role`, `permissions`, `created_at`, `updated_at`) VALUES
HDR;

$sql .= "\n".implode(",\n", $lines);
$sql .= "\nON DUPLICATE KEY UPDATE `username`=VALUES(`username`), `password`=VALUES(`password`), `role`=VALUES(`role`), `updated_at`=NOW();\n";

$dir = __DIR__.'/../database/sql';
file_put_contents($dir.'/seed_admins.sql', $sql);

$minimum = file_get_contents($dir.'/seed_minimum.sql');
// replace admin block - simpler to rebuild seed_minimum entirely
$site = <<<'SITE'
INSERT INTO `site_settings` (`id`, `data`, `created_at`, `updated_at`) VALUES
(
  1,
  '{"site_name":"Alisulasyon","primary_color":"#ff0000","index_primary_color":"#3df5e9","active_theme":"default","maintenance_mode":false,"xp_system_enabled":false,"require_email_verification":true,"telegram_bot_username":"alisulasyonresmibot","slider_layout":"single","sponsor_border_effect":true,"sponsor_card_style":"detailed","background_type":"image","chat_enabled":false,"chat_bot_name":"Nexu Bot","quick_access_style":"design1","register_terms_text":"18 yaşından büyük olduğumu onaylıyorum."}',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `updated_at` = NOW();
SITE;

$full = "-- Alisulasyon minimum seed\nSET NAMES utf8mb4;\n\n".$sql."\n\n".$site."\n";
file_put_contents($dir.'/seed_minimum.sql', $full);

echo "OK seed_admins.sql + seed_minimum.sql güncellendi\n";
