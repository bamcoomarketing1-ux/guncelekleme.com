<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketParticipation extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'ticket_event_id', 'status', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketEvent(): BelongsTo
    {
        return $this->belongsTo(TicketEvent::class);
    }

    public function ticketNumber(): string
    {
        return $this->payload['ticket_number'] ?? str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isWinner(): bool
    {
        return (bool) ($this->payload['is_winner'] ?? false);
    }

    /** @return array<string, mixed> */
    public function toAdminTicketArray(): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticketNumber(),
            'is_winner' => $this->isWinner(),
        ];
    }

    /** @return array<string, mixed> */
    public function toSearchTicketArray(): array
    {
        $user = $this->relationLoaded('user') ? $this->user : null;
        $event = $this->relationLoaded('ticketEvent') ? $this->ticketEvent : null;

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticketNumber(),
            'is_winner' => $this->isWinner(),
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'user' => [
                'id' => $this->user_id,
                'avatar' => $user?->avatar,
                'name' => $user?->name ?: ($user?->username ?? 'Kullanıcı'),
                'username' => $user?->username ?? '',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function userNestedArray(?User $user): array
    {
        return [
            'avatar' => $user?->avatar,
            'name' => $user?->name ?: ($user?->username ?? 'Bilinmeyen'),
            'username' => $user?->username ?? '',
            'email' => $user?->email ?? '',
        ];
    }
}
