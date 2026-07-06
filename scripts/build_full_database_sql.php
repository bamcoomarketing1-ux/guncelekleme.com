#!/usr/bin/env php
<?php
/**
 * Backup/clone verisinden tam MySQL dump üret.
 * Çıktı: database/sql/full_database.sql (şema + tüm veriler)
 *
 * Kullanım: php scripts/build_full_database_sql.php
 */
declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$outFile = __DIR__.'/../database/sql/full_database.sql';
$schemaFile = __DIR__.'/../database/sql/schema_mysql.sql';

echo "=== migrate:fresh + seed ===\n";
Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$backup = realpath(__DIR__.'/../../alisulasyon51/backup') ?: realpath(__DIR__.'/../../alisulasyon-clone/data/backup');
if ($backup) {
    Artisan::call('wheel:import-history', ['--backup' => $backup]);
    echo Artisan::output();
}

if (! is_file($schemaFile)) {
    fwrite(STDERR, "schema_mysql.sql bulunamadi — once schema dosyasi olusturulmali.\n");
    exit(1);
}

$skip = ['sqlite_sequence', 'migrations'];
$tables = collect(Schema::getTableListing())
    ->map(function ($t) {
        return str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t;
    })
    ->unique()
    ->filter(fn ($t) => ! in_array($t, $skip, true))
    ->sort()
    ->values();

echo "Tablolar: ".$tables->count()."\n";

$buf = "-- =============================================================================\n";
$buf .= "-- Alisulasyon FULL DATABASE — şema + clone backup verisi\n";
$buf .= "-- phpMyAdmin / mysql import: full_database.sql\n";
$buf .= "-- Uretim: ".date('c')."\n";
$buf .= "-- =============================================================================\n\n";
$buf .= "SET NAMES utf8mb4;\n";
$buf .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
$buf .= file_get_contents($schemaFile);
$buf .= "\n\n-- ===================== VERİLER =====================\n\n";

$totalRows = 0;
$formatSqlValue = static function ($v): string {
    if ($v === null) {
        return 'NULL';
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    $s = (string) $v;
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $s)) {
        try {
            $s = (new DateTimeImmutable($s))->format('Y-m-d H:i:s');
        } catch (Exception) {
            // olduğu gibi bırak
        }
    }

    return "'".str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", "\\r"], $s)."'";
};

foreach ($tables as $table) {
    $rows = DB::table($table)->get();
    if ($rows->isEmpty()) {
        continue;
    }
    $buf .= "-- {$table} ({$rows->count()} satir)\n";
    $buf .= "DELETE FROM `{$table}`;\n";
    foreach ($rows as $row) {
        $arr = (array) $row;
        $cols = array_map(fn ($c) => '`'.$c.'`', array_keys($arr));
        $vals = array_map($formatSqlValue, array_values($arr));
        $buf .= 'INSERT INTO `'.$table.'` ('.implode(', ', $cols).') VALUES ('.implode(', ', $vals).");\n";
        $totalRows++;
    }
    $buf .= "\n";
}

$buf .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
$buf .= "-- ========== GIRIS BILGILERI ==========\n";
$buf .= "-- Admin panel /panel/login:\n";
$buf .= "--   test@gmail.com / testtest\n";
$buf .= "--   adminadminadminadminadmin@gmail.com / adminadminadminadminadmin\n";
$buf .= "-- Kullanici giris (tum import edilen uyeler): sifre Test123.\n";

file_put_contents($outFile, $buf);
$mb = round(filesize($outFile) / 1024 / 1024, 2);
echo "Yazildi: {$outFile} ({$mb} MB, {$totalRows} satir)\n";
