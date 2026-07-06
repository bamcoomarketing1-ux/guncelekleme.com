<?php

/**
 * MySQL dener; başarısız olursa SQLite fallback.
 */
$root = dirname(__DIR__);
$envFile = $root.'/.env';
$env = file_get_contents($envFile);

function patchEnv(string $env, array $pairs): string
{
    foreach ($pairs as $key => $value) {
        if (preg_match("/^{$key}=.*$/m", $env)) {
            $env = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $env);
        } else {
            $env .= "\n{$key}={$value}";
        }
    }
    return $env;
}

$host = '127.0.0.1';
$port = 3306;
$db = 'alisulasyon';
$user = 'root';
$passwords = ['', 'root'];

foreach ($passwords as $pass) {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $env = patchEnv($env, [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => (string) $port,
            'DB_DATABASE' => $db,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $pass,
            'APP_URL' => 'http://127.0.0.1:8000',
            'APP_NAME' => 'Alisulasyon',
        ]);
        file_put_contents($envFile, $env);
        echo "MySQL OK: {$db}\n";
        exit(0);
    } catch (Throwable $e) {
        $last = $e;
    }
}

$sqlite = $root.'/database/database.sqlite';
if (! file_exists($sqlite)) {
    touch($sqlite);
}
$env = patchEnv($env, [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => 'database/database.sqlite',
    'APP_URL' => 'http://127.0.0.1:8000',
    'APP_NAME' => 'Alisulasyon',
]);
file_put_contents($envFile, $env);
echo "MySQL yok — SQLite kullaniliyor: {$sqlite}\n";
echo "Not: ".($last->getMessage() ?? 'MySQL kapali')."\n";
exit(0);
