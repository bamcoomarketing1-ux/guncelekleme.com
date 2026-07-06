<?php

namespace App\Console\Commands;

use App\Services\FrontendPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallPlatform extends Command
{
    protected $signature = 'platform:install
        {--fresh : migrate:fresh çalıştır}
        {--seed : Yedek veriyi import et}
        {--storage : Medya dosyalarını indir (Python script)}
        {--wheel : Wheel geçmişini import et}';

    protected $description = 'Frontend yayınla + storage link + isteğe bağlı migrate/seed (tek komut kurulum)';

    public function handle(FrontendPublisher $publisher): int
    {
        $this->info('Frontend yayınlanıyor (resources/frontend → public/)...');
        $publisher->publish();
        $this->info('Frontend hazır.');

        $publicStorage = public_path('storage');
        $appStorage = storage_path('app/public');
        if (! is_link($publicStorage) && ! is_dir($publicStorage)) {
            File::ensureDirectoryExists($appStorage);
            if (PHP_OS_FAMILY === 'Windows') {
                if (! is_dir($publicStorage)) {
                    File::copyDirectory($appStorage, $publicStorage);
                }
            } else {
                Artisan::call('storage:link');
            }
            $this->info('Storage bağlandı.');
        }

        if ($this->option('fresh')) {
            $this->warn('migrate:fresh çalışıyor...');
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->line(Artisan::output());
        }

        if ($this->option('seed')) {
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(Artisan::output());
        }

        if ($this->option('storage')) {
            $script = base_path('scripts/download_storage.py');
            if (is_file($script)) {
                $this->info('Medya indiriliyor...');
                $python = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';
                passthru($python.' '.escapeshellarg($script), $code);
                if ($code !== 0) {
                    $this->warn('Bazı medya dosyaları indirilemedi (canlıda 404 olabilir).');
                }
            }
        }

        if ($this->option('wheel')) {
            $backup = realpath(base_path('../alisulasyon51/backup'));
            if ($backup) {
                Artisan::call('wheel:import-history', ['--backup' => $backup]);
                $this->line(Artisan::output());
            }
        }

        $this->newLine();
        $this->info('Kurulum tamamlandı.');
        $this->line('Site:  '.config('app.url'));
        $this->line('Panel: '.rtrim(config('app.url'), '/').'/panel/login');
        $this->line('Çalıştır: php artisan serve');

        return self::SUCCESS;
    }
}
