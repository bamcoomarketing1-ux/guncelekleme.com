<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class MarketOrder extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['user_id', 'market_product_id', 'status', 'payload'];
    protected $casts = [
        'payload' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $payload = is_array($this->payload) ? $this->payload : [];
        $row['wallet_details'] = $payload['wallet_details'] ?? $payload['wallet'] ?? null;
        $row['price_at_purchase'] = $payload['price'] ?? $this->product?->price ?? 0;
        $row['created_at'] = $this->created_at?->toISOString();
        $row['user'] = $this->user ? [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
        ] : null;
        $row['product'] = $this->product ? $this->product->toApiArray() : null;

        return $row;
    }
}
