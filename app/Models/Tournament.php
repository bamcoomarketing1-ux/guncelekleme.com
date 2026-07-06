<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use MapsApiFields;

    protected $fillable = ['title', 'description', 'image_url', 'size', 'status', 'winner', 'matches', 'participants', 'is_active'];

    protected $casts = [
        'matches' => 'array',
        'participants' => 'array',
        'winner' => 'array',
        'is_active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'size' => $this->size ?? 8,
            'status' => $this->normalizedStatus(),
            'winner' => $this->winnerSummary(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }

    private function normalizedStatus(): string
    {
        $status = $this->status ?? 'setup';
        if (in_array($status, ['setup', 'active', 'completed'], true)) {
            return $status;
        }

        return $this->is_active ? 'active' : 'setup';
    }

    public function toListApiArray(): array
    {
        return $this->toApiArray();
    }

    public function toDetailApiArray(): array
    {
        $participants = collect($this->participants ?? [])->map(function (array $p, int $i) {
            $userId = $p['user']['id'] ?? $p['user_id'] ?? null;
            $username = $p['user']['username'] ?? $p['username'] ?? null;

            return [
                'id' => $p['id'] ?? ($i + 1),
                'slot' => $p['slot'] ?? ($i + 1),
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'name' => $p['user']['name'] ?? $p['name'] ?? $username,
                ],
                'user_id' => $userId,
            ];
        })->values()->all();

        $winner = $this->winner;
        if (! is_array($winner) && $this->winnerSummary()) {
            $winner = $this->winnerSummary();
        }

        return [
            'tournament' => [
                'id' => $this->id,
                'name' => $this->title,
                'title' => $this->title,
                'size' => $this->size ?? 8,
                'status' => $this->normalizedStatus(),
                'winner' => $winner,
            ],
            'participants' => $participants,
            'matches' => $this->matches ?? [],
        ];
    }

    private function winnerSummary(): ?array
    {
        $winner = $this->winner;
        if (! is_array($winner)) {
            return null;
        }
        if (isset($winner['user']['username'])) {
            return ['username' => $winner['user']['username']];
        }
        if (isset($winner['username'])) {
            return ['username' => $winner['username']];
        }

        return null;
    }
}
