<?php

namespace App\Console\Commands;

use App\Models\WheelSpin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportWheelHistory extends Command
{
    protected $signature = 'wheel:import-history {--api= : Live API base URL} {--token= : Admin bearer token} {--backup= : Backup folder path}';

    protected $description = 'Wheel geçmişini backup veya canlı API\'den import et';

    public function handle(): int
    {
        $backup = $this->option('backup') ?: realpath(base_path('../alisulasyon51/backup'));
        $imported = 0;

        if ($backup && is_file($backup.'/api/admin/admin_wheel_history.json')) {
            $imported += $this->importFromJson($backup.'/api/admin/admin_wheel_history.json');
        }

        $api = rtrim((string) $this->option('api'), '/');
        $token = (string) $this->option('token');
        if ($api && $token) {
            $page = 1;
            do {
                $response = Http::withToken($token)->get("{$api}/admin/wheel/history", ['page' => $page, 'per_page' => 50]);
                if (! $response->successful()) {
                    break;
                }
                $body = $response->json();
                $rows = $body['data'] ?? [];
                foreach ($rows as $row) {
                    if (empty($row['id'])) {
                        continue;
                    }
                    WheelSpin::updateOrCreate(['id' => $row['id']], [
                        'user_id' => $row['user_id'] ?? null,
                        'wheel_prize_id' => $row['wheel_item_id'] ?? $row['wheel_prize_id'] ?? null,
                        'reward' => $row['reward_amount'] ?? 0,
                        'reward_amount' => $row['reward_amount'] ?? 0,
                        'reward_type' => $row['reward_type'] ?? 'balance',
                        'is_combo_spin' => $row['is_combo_spin'] ?? false,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                    $imported++;
                }
                $page++;
                $lastPage = (int) ($body['last_page'] ?? 1);
            } while ($page <= $lastPage);
        }

        $this->info("Toplam {$imported} wheel kaydı import edildi.");

        return self::SUCCESS;
    }

    private function importFromJson(string $path): int
    {
        $raw = json_decode(file_get_contents($path), true);
        $body = $raw['body'] ?? $raw;
        $count = 0;
        foreach ($body['data'] ?? [] as $row) {
            if (empty($row['id'])) {
                continue;
            }
            WheelSpin::updateOrCreate(['id' => $row['id']], [
                'user_id' => $row['user_id'] ?? null,
                'wheel_prize_id' => $row['wheel_item_id'] ?? $row['wheel_prize_id'] ?? null,
                'reward' => $row['reward_amount'] ?? 0,
                'reward_amount' => $row['reward_amount'] ?? 0,
                'reward_type' => $row['reward_type'] ?? 'balance',
                'is_combo_spin' => $row['is_combo_spin'] ?? false,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
            $count++;
        }

        return $count;
    }
}
