<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class LinkItem extends Model
{
    use MapsApiFields;

    protected $fillable = ['title', 'url', 'icon', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
