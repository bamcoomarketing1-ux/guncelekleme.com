<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BackupImportSeeder::class);

        // Kalıcı Admin Hesabı Oluşturma/Güncelleme
        \App\Models\Admin::updateOrCreate(
            ['email' => 'bamcoomarketing1@gmail.com'],
            [
                'username' => 'bamcoomarketing',
                'role' => 'Sistem Yöneticisi',
                'password' => \Illuminate\Support\Facades\Hash::make('Zaq.12345'),
            ]
        );

        // Kalıcı Normal Kullanıcı Hesabı Oluşturma/Güncelleme
        \App\Models\User::updateOrCreate(
            ['email' => 'bamcoomarketing1@gmail.com'],
            [
                'name' => 'Bamcoo Marketing',
                'username' => 'bamcoomarketing',
                'password' => \Illuminate\Support\Facades\Hash::make('Zaq.12345'),
            ]
        );

        // Kalıcı Telegram Bot Ayarları
        \App\Models\TelegramSetting::updateOrCreate(
            ['id' => 1],
            [
                'bot_token' => '8122513380:AAEiq1GMdaan-5CnLStzNBJjZoCPF9pdvBo',
                'bot_username' => 'deneme_2026_bot',
                'is_active' => true,
            ]
        );
    }
}
