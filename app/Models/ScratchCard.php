<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class ScratchCard extends Model
{
    use MapsApiFields;

    protected $fillable = ['title', 'description', 'reward_amount', 'weight', 'is_active'];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
