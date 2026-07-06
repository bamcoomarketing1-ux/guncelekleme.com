<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialOddBet extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'special_odd_id', 'amount', 'status', 'payout'];

    protected $casts = ['amount' => 'decimal:2', 'payout' => 'decimal:2'];

    public function specialOdd(): BelongsTo
    {
        return $this->belongsTo(SpecialOdd::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
