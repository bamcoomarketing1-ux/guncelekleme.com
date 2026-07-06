<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class XpReward extends Model
{
    use MapsApiFields;

    protected $fillable = ['action', 'label', 'xp_amount', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
