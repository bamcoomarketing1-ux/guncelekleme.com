<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use MapsApiFields;

    protected $fillable = ['league_id', 'name', 'logo_url', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
