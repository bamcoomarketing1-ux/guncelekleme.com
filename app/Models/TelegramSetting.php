<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSetting extends Model
{
    protected $fillable = ['bot_token', 'bot_username', 'webhook_url', 'is_active', 'extra'];

    protected $casts = ['is_active' => 'boolean', 'extra' => 'array'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'bot_username' => 'alisulasyonresmibot',
            'is_active' => true,
        ]);
    }
}
