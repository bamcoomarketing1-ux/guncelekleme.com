<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use MapsApiFields;

    protected $fillable = ['title', 'content', 'image_url', 'is_active', 'sort_order'];
    protected $casts = [
            'is_active' => 'boolean',
    ];

}
