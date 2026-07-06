<?php
/**
 * MySQL veritabanı oluştur ve .env ayarla.
 */
$envFile = __DIR__.'/../.env';
$env = file_exists($envFile) ? file_get_contents($envFile) : '';

$host = '127.0.0.1';
$port = 3306;
$db = 'alisulasyon';
$user = 'root';

$replacements = [
    'DB_CONNECTION=sqlite' => 'DB_CONNECTION=mysql',
    '# DB_HOST=127.0.0.1' => 'DB_HOST=127.0.0.1',
    '# DB_PORT=3306' => 'DB_PORT=3306',
    '# DB_DATABASE=laravel' => 'DB_DATABASE=alisulasyon',
    '# DB_USERNAME=root' => 'DB_USERNAME=root',
    '# DB_PASSWORD=' => 'DB_PASSWORD=root',
    'APP_URL=http://localhost' => 'APP_URL=http://127.0.0.1:8000',
    'APP_NAME=Laravel' => 'APP_NAME=Alisulasyon',
];

foreach ($replacements as $from => $to) {
    $env = str_replace($from, $to, $env);
}
file_put_contents($envFile, $env);

$passwords = array_unique([getenv('DB_PASSWORD') ?: '', 'root', '']);

foreach ($passwords as $pass) {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $env = str_replace('DB_PASSWORD=root', 'DB_PASSWORD='.$pass, $env);
        $env = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD='.$pass, $env);
        file_put_contents($envFile, $env);
        echo "MySQL OK: database `{$db}` (password ".($pass === '' ? 'empty' : 'set').")\n";
        exit(0);
    } catch (Throwable $e) {
        $last = $e;
    }
}

fwrite(STDERR, "MySQL HATA: ".($last->getMessage() ?? 'connection failed')."\n");
fwrite(STDERR, "XAMPP/WAMP MySQL servisini başlatın veya: docker compose up -d\n");
exit(1);
