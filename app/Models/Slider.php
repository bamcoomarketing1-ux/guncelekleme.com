<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['title', 'image_url', 'link', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['image'] = $row['image_url'] ?? null;

        return $row;
    }
}
