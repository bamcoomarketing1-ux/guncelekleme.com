<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'id', 'name', 'username', 'email', 'phone', 'password', 'balance', 'xp', 'level',
    'is_moderator', 'is_active', 'avatar', 'wallet_trc20', 'wallet_erc20', 'wallet_iban',
    'telegram_chat_id', 'telegram_username', 'telegram_first_name', 'telegram_verified_at',
    'verification_code', 'verification_expires_at',
    'telegram_verification_code', 'telegram_verification_expires_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'telegram_verified_at' => 'datetime',
            'telegram_verification_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_moderator' => 'boolean',
            'is_active' => 'boolean',
            'balance' => 'decimal:2',
        ];
    }

    public function userSponsors()
    {
        return $this->hasMany(UserSponsor::class);
    }
}
