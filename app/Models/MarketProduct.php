<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class MarketProduct extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['title', 'description', 'price', 'image_path', 'required_wallets', 'is_active', 'sort_order'];

    protected $casts = [
        'price' => 'decimal:2',
        'required_wallets' => 'array',
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['price'] = number_format((float) $this->price, 2, '.', '');
        $row['description'] = $this->description ?? '';
        $image = self::normalizeProductImage($this->image_path);
        $row['image_path'] = $image;
        $row['image'] = $image;
        $row['image_preview'] = $image;
        $row['required_wallets'] = $this->required_wallets ?? [];

        return $row;
    }

    public static function normalizeProductImage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim($path);
        if ($path === '' || $path === '#' || preg_match('#^data:#i', $path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $path = preg_replace('#https?://[^/]+/storage/#', '/storage/', $path) ?? $path;
        } elseif (str_starts_with($path, 'storage/')) {
            $path = '/'.$path;
        } elseif (! str_starts_with($path, '/')) {
            $path = '/storage/'.ltrim($path, '/');
        }

        if (! str_starts_with($path, '/storage/')) {
            return null;
        }

        return $path;
    }
}
