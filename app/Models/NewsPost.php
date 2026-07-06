<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'content', 'image_url', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['is_published'] = $this->is_active;
        $row['order'] = $this->sort_order ?? 0;
        $row['category'] = $this->category;
        $row['excerpt'] = $this->excerpt;
        $row['slug'] = $this->slug;

        return $row;
    }
}
