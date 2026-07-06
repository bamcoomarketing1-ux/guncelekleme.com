<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = [
        'sponsor_id', 'title', 'description', 'image_url', 'total_tickets', 'ticket_price',
        'event_date', 'is_active', 'show_on_homepage', 'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_homepage' => 'boolean',
        'event_date' => 'datetime',
        'ticket_price' => 'decimal:2',
        'sponsor_id' => 'integer',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $row['sponsor_id'] = $this->sponsor_id;
        $row['sponsor'] = $this->relationLoaded('sponsor') && $this->sponsor
            ? $this->sponsor->toNestedApiArray()
            : ($this->sponsor_id ? Sponsor::find($this->sponsor_id)?->toNestedApiArray() : null);
        $row['end_date'] = $this->event_date?->format('d.m.Y H:i:s') ?? '';
        $row['event_date'] = $this->event_date?->toISOString();
        $row['status'] = $this->resolveStatus();
        $row['ticket_price'] = $this->ticket_price;

        return $row;
    }

    public function resolveStatus(): string
    {
        if (($this->status ?? '') === 'ended' || ! $this->is_active) {
            return 'ended';
        }

        return 'active';
    }

    public function toDetailApiArray(): array
    {
        $row = $this->toApiArray();
        $row['tickets_count'] = TicketParticipation::where('ticket_event_id', $this->id)->count();
        $row['requests_count'] = TicketRequest::where('ticket_event_id', $this->id)->count();
        $row['description'] = $this->description ?? '';
        $row['image'] = $row['image_url'] ?? null;

        return $row;
    }
}
