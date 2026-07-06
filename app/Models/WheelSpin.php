<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class WheelSpin extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'wheel_prize_id', 'reward'];
    protected $casts = [
        'reward' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'is_combo_spin' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prize()
    {
        return $this->belongsTo(WheelPrize::class, 'wheel_prize_id');
    }

}
