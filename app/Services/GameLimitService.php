<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\ScratchCardPlay;
use Illuminate\Support\Carbon;

class GameLimitService
{
    public function minesPlaysToday(int $userId): int
    {
        return GameSession::where('user_id', $userId)
            ->where('game', 'mines')
            ->whereDate('created_at', today())
            ->count();
    }

    public function dicePlaysToday(int $userId): int
    {
        return GameSession::where('user_id', $userId)
            ->where('game', 'dice')
            ->whereDate('created_at', today())
            ->count();
    }

    public function scratchPlaysToday(int $userId): int
    {
        return ScratchCardPlay::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();
    }

    public function assertMinesAllowed(int $userId): void
    {
        $max = config('platform.limits.mines_daily_plays', 100);
        if ($this->minesPlaysToday($userId) >= $max) {
            throw new \RuntimeException("Günlük mines limitine ulaştınız ({$max}).");
        }
    }

    public function assertDiceAllowed(int $userId): void
    {
        $max = config('platform.limits.dice_daily_plays', 100);
        if ($this->dicePlaysToday($userId) >= $max) {
            throw new \RuntimeException("Günlük dice limitine ulaştınız ({$max}).");
        }
    }

    public function assertScratchAllowed(int $userId): void
    {
        $max = config('platform.limits.scratch_daily_plays', 5);
        if ($this->scratchPlaysToday($userId) >= $max) {
            throw new \RuntimeException("Günlük kazı kazan limitine ulaştınız ({$max}).");
        }
    }

    public function dailyStats(int $userId, string $game): array
    {
        $map = [
            'mines' => ['count' => $this->minesPlaysToday($userId), 'max' => config('platform.limits.mines_daily_plays', 100)],
            'dice' => ['count' => $this->dicePlaysToday($userId), 'max' => config('platform.limits.dice_daily_plays', 100)],
            'scratch' => ['count' => $this->scratchPlaysToday($userId), 'max' => config('platform.limits.scratch_daily_plays', 5)],
        ];

        $stats = $map[$game] ?? ['count' => 0, 'max' => 0];

        return ['plays_today' => $stats['count'], 'max_plays' => $stats['max']];
    }
}
