#!/usr/bin/env php
<?php
/**
 * Mevcut (SQL import) veritabanında migrations tablosunu senkronize eder,
 * ardından sadece eksik migration'ları çalıştırır.
 *
 * Kullanım (sunucuda):
 *   php83 scripts/sync_production_migrations.php
 */
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! Schema::hasTable('migrations')) {
    echo "migrations tablosu oluşturuluyor...\n";
    Artisan::call('migrate:install');
    echo Artisan::output();
}

/** @var list<string> */
$ran = DB::table('migrations')->pluck('migration')->all();

/** @var array<string, callable(): bool> */
$checks = [
    '0001_01_01_000000_create_users_table' => fn () => Schema::hasTable('users'),
    '0001_01_01_000001_create_cache_table' => fn () => Schema::hasTable('cache'),
    '0001_01_01_000002_create_jobs_table' => fn () => Schema::hasTable('jobs'),
    '2026_06_24_000010_create_alisulasyon_platform' => fn () => Schema::hasTable('sponsors'),
    '2026_06_24_213744_create_personal_access_tokens_table' => fn () => Schema::hasTable('personal_access_tokens'),
    '2026_06_25_000020_create_level_rewards_table' => fn () => Schema::hasTable('level_rewards'),
    '2026_06_25_000030_add_user_account_compat' => fn () => Schema::hasTable('user_sponsors'),
    '2026_06_25_000040_extend_scratch_card_plays' => fn () => Schema::hasColumn('scratch_card_plays', 'is_scratched'),
    '2026_06_25_000050_extend_raffles' => fn () => Schema::hasColumn('raffles', 'reward_type'),
    '2026_06_25_000060_extend_trial_bonuses' => fn () => Schema::hasColumn('trial_bonuses', 'sponsor_id'),
    '2026_06_25_000061_extend_bonuses' => fn () => Schema::hasColumn('bonuses', 'sponsor_id'),
];

$batch = (int) (DB::table('migrations')->max('batch') ?: 0);
$marked = 0;

foreach ($checks as $migration => $exists) {
    if (in_array($migration, $ran, true)) {
        continue;
    }

    if (! $exists()) {
        echo "Bekliyor (şema yok): {$migration}\n";
        continue;
    }

    $batch++;
    DB::table('migrations')->insert([
        'migration' => $migration,
        'batch' => $batch,
    ]);
    echo "İşaretlendi (zaten mevcut): {$migration}\n";
    $marked++;
}

echo "\n{$marked} migration kaydı eklendi.\n";
echo "Eksik migration'lar çalıştırılıyor...\n\n";

Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();

echo "\nDurum:\n";
Artisan::call('migrate:status');
echo Artisan::output();
