<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    use MapsApiFields {
        toApiArray as protected baseApiArray;
    }

    protected $fillable = [
        'title', 'description', 'image_url', 'ticket_price', 'reward_type', 'total_prize',
        'winner_count', 'rules', 'ends_at', 'starts_at', 'is_active', 'status',
        'winner_user_id', 'drawn_at', 'max_tickets_per_user',
    ];

    protected $casts = [
        'ticket_price' => 'decimal:2',
        'is_active' => 'boolean',
        'ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'drawn_at' => 'datetime',
        'winner_count' => 'integer',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(RaffleParticipant::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function toApiArray(): array
    {
        $row = $this->baseApiArray();
        $row['participant_count'] = (int) $this->participants()->sum('ticket_count');
        $row['participants_count'] = $row['participant_count'];

        return $row;
    }

    public function toPublicApiArray(?User $user = null): array
    {
        $row = $this->toApiArray();
        $startsAt = $this->starts_at ?? $this->created_at;
        $status = $this->resolveStatus();

        $row['image'] = $row['image_url'] ?? null;
        $row['status'] = $status;
        $row['reward_type'] = $this->reward_type ?: 'points';
        $row['total_prize'] = $this->total_prize ?? (string) $this->ticket_price;
        $row['winner_count'] = $this->winner_count ?: 1;
        $row['rules'] = $this->rules ?? $this->description ?? '';
        $row['start_date_human'] = $this->formatHumanDate($startsAt);
        $row['end_date_human'] = $this->formatHumanDate($this->ends_at) ?: '-';
        $row['time_left_human'] = $this->timeLeftHuman();
        $row['progress'] = $this->progressPercent($startsAt);

        if ($user) {
            $row['is_participated'] = $this->participants()->where('user_id', $user->id)->exists();
        }

        return $row;
    }

    public function winnersList(): array
    {
        if (! $this->drawn_at && $this->resolveStatus() !== 'ended') {
            return [];
        }

        if ($this->winner) {
            return [['username' => $this->winner->username]];
        }

        return [];
    }

    public function resolveStatus(): string
    {
        if ($this->drawn_at) {
            return 'ended';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'picking';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'upcoming';
        }

        if (in_array($this->status, ['active', 'upcoming', 'ended', 'picking'], true)) {
            return $this->status;
        }

        return $this->is_active ? 'active' : 'ended';
    }

    private function formatHumanDate(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->copy()->locale('tr')->translatedFormat('d M Y H:i');
    }

    private function timeLeftHuman(): string
    {
        if ($this->drawn_at) {
            return 'Bitti';
        }

        if (! $this->ends_at) {
            return '...';
        }

        if ($this->ends_at->isPast()) {
            return 'Çekiliş yapılıyor';
        }

        return $this->ends_at->copy()->locale('tr')->diffForHumans(now(), [
            'parts' => 2,
            'syntax' => Carbon::DIFF_ABSOLUTE,
        ]);
    }

    private function progressPercent(?Carbon $startsAt): int
    {
        if ($this->drawn_at) {
            return 100;
        }

        $start = $startsAt?->timestamp ?? 0;
        $end = $this->ends_at?->timestamp ?? 0;

        if ($end <= $start) {
            return 0;
        }

        $now = now()->timestamp;

        return (int) min(100, max(0, round(($now - $start) / ($end - $start) * 100)));
    }
}
