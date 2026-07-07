<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadService
{
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    ];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const BLOCKED_EXTENSIONS = [
        'php', 'phps', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8',
        'exe', 'sh', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm', 'shtml',
    ];

    public function storeImage(UploadedFile $file, string $folder): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => ['Dosya yüklenemedi: '.$this->uploadErrorMessage($file->getError())],
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');
        $mime = $file->getMimeType();

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => ['Bu dosya türüne izin verilmiyor.']]);
        }

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => ['Sadece resim dosyaları yüklenebilir.']]);
        }

        if ($mime && ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['file' => ['Geçersiz dosya içeriği.']]);
        }

        $path = $file->getRealPath() ?: $file->getPathname();

        if ($ext === 'svg') {
            $contents = file_get_contents($path, false, null, 0, 1024 * 1024);
            if ($contents && preg_match('/<\?(php|=)|<script/i', $contents)) {
                throw ValidationException::withMessages(['file' => ['Dosya polyglot içerik barındırıyor.']]);
            }
        } elseif (@getimagesize($path) === false) {
            throw ValidationException::withMessages(['file' => ['Geçersiz resim dosyası.']]);
        }

        self::ensurePublicStorage();

        $name = Str::uuid().'.'.$ext;
        $folder = trim(str_replace('\\', '/', $folder), '/');
        $disk = Storage::disk('public');

        if ($folder !== '' && ! $disk->exists($folder)) {
            $disk->makeDirectory($folder);
        }

        $stored = $file->storeAs($folder, $name, 'public');

        if ($stored === false || ! $disk->exists($stored)) {
            throw ValidationException::withMessages(['file' => ['Dosya kaydedilemedi. public/storage yazılabilir mi kontrol edin.']]);
        }

        self::mirrorToPublicStorage($stored);

        // Railway gibi kısıtlı ortamlarda public/storage'a da direkt yaz
        try {
            $storedRelPath = ltrim(str_replace('\\', '/', $stored), '/');
            $pubStorageDest = public_path('storage/'.$storedRelPath);
            $pubStorageDir = dirname($pubStorageDest);
            if (! is_dir($pubStorageDir)) {
                @mkdir($pubStorageDir, 0755, true);
            }
            $source = storage_path('app/public/'.$storedRelPath);
            if (is_file($source) && ! is_file($pubStorageDest)) {
                @copy($source, $pubStorageDest);
            }
        } catch (\Throwable $e) {
            // Hata yut
        }

        return '/storage/'.$stored;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu sunucu limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya yalnızca kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör eksik.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
            default => 'Bilinmeyen yükleme hatası.',
        };
    }

    public static function mirrorToPublicStorage(string $storedRelativePath): void
    {
        try {
            $storedRelativePath = ltrim(str_replace('\\', '/', $storedRelativePath), '/');
            $source = storage_path('app/public/'.$storedRelativePath);
            $dest = public_path('storage/'.$storedRelativePath);

            if (! is_file($source)) {
                return;
            }

            $destDir = dirname($dest);
            if (! is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }

            if (is_dir($destDir) && ! is_file($dest)) {
                @copy($source, $dest);
            }
        } catch (\Throwable $e) {
            // Railway gibi kısıtlı dosya sistemlerinde hata vermesini engelle
        }
    }

    public static function resolvePublicPath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, strlen('storage/'));
        }

        foreach ([
            public_path('storage/'.$relativePath),
            storage_path('app/public/'.$relativePath),
            storage_path('app/private/public/'.$relativePath),
        ] as $full) {
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    public static function publicStorageRoot(): string
    {
        return public_path('storage');
    }

    public static function ensurePublicStorage(): void
    {
        try {
            $publicStorage = self::publicStorageRoot();

            if (is_link($publicStorage)) {
                if (is_dir($publicStorage)) {
                    return;
                }

                @unlink($publicStorage);
            }

            if (file_exists($publicStorage) && ! is_dir($publicStorage)) {
                @unlink($publicStorage);
            }

            if (! is_dir($publicStorage)) {
                @mkdir($publicStorage, 0755, true);
            }
        } catch (\Throwable $e) {
            // Hata yut, Railway üzerinde çökmeyi engelle
        }
    }

    /** @deprecated use ensurePublicStorage() */
    public static function ensurePublicLink(): void
    {
        self::ensurePublicStorage();
    }

    public static function copyTree(string $source, string $destination): int
    {
        if (! is_dir($source)) {
            return 0;
        }

        $copied = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            $relative = substr($item->getPathname(), strlen($source) + 1);
            $relative = str_replace('\\', '/', $relative);
            $dest = $destination.'/'.$relative;

            if (! is_dir(dirname($dest))) {
                mkdir(dirname($dest), 0755, true);
            }

            if (! is_file($dest) && copy($item->getPathname(), $dest)) {
                $copied++;
            }
        }

        return $copied;
    }
}
