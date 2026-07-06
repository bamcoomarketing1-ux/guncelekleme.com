<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use MapsApiFields;

    protected $fillable = ['name', 'country', 'logo_url', 'is_active'];
    protected $casts = [
            'is_active' => 'boolean',
    ];

}
