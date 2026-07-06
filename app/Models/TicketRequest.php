<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequest extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['user_id', 'ticket_event_id', 'status', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketEvent(): BelongsTo
    {
        return $this->belongsTo(TicketEvent::class);
    }

    /** @return array<string, mixed> */
    public function toAdminApiArray(): array
    {
        $payload = $this->payload ?? [];
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'investment_amount' => $payload['investment_amount'] ?? 0,
            'screenshot_url' => $this->normalizeScreenshotUrl($payload['screenshot_url'] ?? null),
            'approved_ticket_count' => $payload['approved_ticket_count'] ?? null,
            'rejection_reason' => $payload['rejection_reason'] ?? null,
            'has_winner' => (bool) ($payload['has_winner'] ?? false),
            'created_at' => $this->created_at?->toISOString(),
            'user' => [
                'name' => $user?->name ?: ($user?->username ?? 'Kullanıcı'),
                'username' => $user?->username ?? '',
                'email' => $user?->email ?? '',
            ],
        ];
    }

    private function normalizeScreenshotUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            return preg_replace('#https?://[^/]+/storage/#', '/storage/', $url) ?? $url;
        }

        if (str_starts_with($url, 'storage/')) {
            return '/'.$url;
        }

        if (! str_starts_with($url, '/')) {
            return '/storage/'.ltrim($url, '/');
        }

        return $url;
    }
}
