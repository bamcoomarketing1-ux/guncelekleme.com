#!/usr/bin/env php
<?php
/**
 * Eski upload dizinlerindeki dosyaları public/storage altına kopyalar.
 *
 * Kullanım (sunucuda):
 *   php83 scripts/fix_storage_uploads.php
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UploadService;

UploadService::ensurePublicStorage();

$targetRoot = UploadService::publicStorageRoot();
$copied = 0;

foreach ([
    storage_path('app/public'),
    storage_path('app/private/public'),
] as $sourceRoot) {
    $copied += UploadService::copyTree($sourceRoot, $targetRoot);
}

echo "{$copied} dosya public/storage altına kopyalandı.\n";
echo 'public/storage yazılabilir: '.(is_writable($targetRoot) ? 'evet' : 'HAYIR')."\n";
echo 'public/storage mevcut: '.(is_dir($targetRoot) ? 'evet' : 'hayır')."\n";

$trialDir = $targetRoot.'/trial-bonuses';
if (is_dir($trialDir)) {
    $count = count(array_filter(scandir($trialDir) ?: [], fn ($f) => $f !== '.' && $f !== '..'));
    echo "trial-bonuses dosya sayısı: {$count}\n";
} else {
    echo "trial-bonuses klasörü henüz yok (yeni upload sonrası oluşur).\n";
}

echo "\nÖrnek URL: /storage/trial-bonuses/<dosya-adı>.jpg\n";
echo "Kontrol: public/storage/trial-bonuses/ klasörüne FTP ile bakın.\n";
