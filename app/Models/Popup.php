<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Popup extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['type', 'title', 'description', 'image_url', 'link', 'link_text', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $imageUrl = $row['image_url'] ?? null;
        $row['image'] = $imageUrl;
        $row['image_url'] = $imageUrl;

        return $row;
    }
}
