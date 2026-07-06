<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {email : Admin e-posta}
                            {password : Admin şifre}
                            {--username= : Kullanıcı adı (opsiyonel)}
                            {--role=Sistem Yöneticisi : Rol}';

    protected $description = 'Yeni admin hesabı oluştur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'username' => $this->option('username') ?: explode('@', $email)[0],
                'password' => Hash::make($this->argument('password')),
                'role' => $this->option('role'),
            ]
        );

        $this->info("Admin hazır: {$admin->email} (id: {$admin->id}, role: {$admin->role})");
        $this->line('Panel: /panel/login');

        return self::SUCCESS;
    }
}
