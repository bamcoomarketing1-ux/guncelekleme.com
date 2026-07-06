<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use MapsApiFields;

    protected $fillable = ['title', 'image_url', 'link', 'position', 'size', 'is_active', 'sort_order'];
    protected $casts = [
            'is_active' => 'boolean',
    ];

}
