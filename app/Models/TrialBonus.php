<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialBonus extends Model
{
    use MapsApiFields {
        toApiArray as protected baseApiArray;
    }

    protected $fillable = [
        'sponsor_id', 'title', 'amount', 'description', 'image_url', 'link',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'sponsor_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (TrialBonus $bonus) {
            if ($bonus->sponsor_id) {
                $sponsor = $bonus->relationLoaded('sponsor')
                    ? $bonus->sponsor
                    : Sponsor::find($bonus->sponsor_id);

                if ($sponsor) {
                    $bonus->link = $sponsor->link ?: $bonus->link;
                }
            }
        });
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function toApiArray(): array
    {
        $this->loadMissing('sponsor');

        $row = $this->baseApiArray();
        $row['sponsor_id'] = $this->sponsor_id ?? '';
        $row['amount'] = (float) $this->amount;
        $link = Sponsor::normalizeExternalLink($this->link ?: $this->sponsor?->link);
        $row['link'] = $link;

        if ($this->sponsor) {
            $sponsor = $this->sponsor->toNestedApiArray();
            $sponsor['link'] = $link;
            $row['sponsor'] = $sponsor;
        } else {
            $row['sponsor'] = null;
        }

        return $row;
    }
}
