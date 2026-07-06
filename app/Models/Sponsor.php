<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sponsor extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['category_id', 'name', 'description', 'logo_url', 'link', 'is_carousel', 'is_active', 'sort_order'];

    protected $casts = [
        'is_carousel' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SponsorCategory::class, 'category_id');
    }

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['category_name'] = $this->relationLoaded('category')
            ? $this->category?->name
            : SponsorCategory::find($this->category_id)?->name;

        return $row;
    }

    public function toNestedApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'link' => self::normalizeExternalLink($this->link),
            'logo_url' => $this->normalizeUrl($this->logo_url),
            'logo_full_url' => $this->normalizeUrl($this->logo_url),
        ];
    }

    public static function normalizeExternalLink(?string $link): ?string
    {
        if ($link === null) {
            return null;
        }

        $link = trim($link);
        if ($link === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $link)) {
            $link = 'https://'.ltrim($link, '/');
        }

        return $link;
    }
}
