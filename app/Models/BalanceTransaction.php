<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'type', 'amount', 'balance_after', 'reference'];
    protected $casts = [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
    ];

}
