<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScratchCardPlay extends Model
{
    protected $fillable = ['user_id', 'scratch_card_id', 'reward_amount', 'is_scratched', 'reward_type', 'payload'];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'is_scratched' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function scratchCard()
    {
        return $this->belongsTo(ScratchCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
