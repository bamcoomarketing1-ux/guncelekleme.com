<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use MapsApiFields;

    protected $fillable = ['code', 'reward_amount', 'usage_limit', 'used_count', 'expired_at', 'is_active'];
    protected $casts = [
            'reward_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'expired_at' => 'datetime',
    ];

}
