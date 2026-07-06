<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class WheelPrize extends Model
{
    use MapsApiFields;

    protected $fillable = ['name', 'type', 'value', 'weight', 'is_active'];
    protected $casts = [
            'value' => 'decimal:2',
            'is_active' => 'boolean',
    ];

}
