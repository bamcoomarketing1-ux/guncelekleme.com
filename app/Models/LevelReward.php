<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelReward extends Model
{
    protected $fillable = ['level', 'reward_type', 'reward_amount'];

    protected function casts(): array
    {
        return ['reward_amount' => 'decimal:2'];
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'reward_type' => $this->reward_type,
            'reward_amount' => (float) $this->reward_amount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
