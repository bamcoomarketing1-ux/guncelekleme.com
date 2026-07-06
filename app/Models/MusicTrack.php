<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class MusicTrack extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['title', 'artist', 'url', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['youtube_url'] = $this->url ?? '';
        $row['artist'] = $this->artist ?? '';

        return $row;
    }
}
