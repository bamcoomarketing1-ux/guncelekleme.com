<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $table = 'social_media';

    protected $fillable = ['platform', 'title', 'url', 'icon_url', 'is_active', 'show_on_homepage', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_homepage' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['name'] = $this->title;
        $row['type'] = $this->platform;
        $row['order'] = $row['sort_order'] ?? 0;

        if (! $row['type'] && $this->url) {
            $row['type'] = $this->guessPlatformFromUrl($this->url);
        }

        return $row;
    }

    private function guessPlatformFromUrl(string $url): ?string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        return match (true) {
            str_contains($host, 't.me') || str_contains($host, 'telegram') => 'telegram',
            str_contains($host, 'youtube') || str_contains($host, 'youtu.be') => 'youtube',
            str_contains($host, 'facebook') || str_contains($host, 'fb.com') => 'facebook',
            str_contains($host, 'instagram') => 'instagram',
            str_contains($host, 'twitter') || str_contains($host, 'x.com') => 'x',
            str_contains($host, 'tiktok') => 'tiktok',
            str_contains($host, 'whatsapp') || str_contains($host, 'wa.me') => 'whatsapp',
            str_contains($host, 'teams.microsoft') => 'teams',
            default => null,
        };
    }
}
