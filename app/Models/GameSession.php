<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'game', 'bet', 'payout', 'status', 'state'];
    protected $casts = [
            'bet' => 'decimal:2',
            'payout' => 'decimal:2',
            'state' => 'array',
    ];

}
