<?php

namespace App\Services;

use App\Models\Raffle;
use App\Models\RaffleParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RaffleService
{
    public function __construct(
        private BalanceService $balance,
        private NotificationService $notifications,
        private XpService $xp,
    ) {}

    public function join(User $user, Raffle $raffle, int $tickets = 1): RaffleParticipant
    {
        if (! $raffle->is_active || ($raffle->ends_at && $raffle->ends_at->isPast())) {
            throw new \RuntimeException('Çekiliş aktif değil.');
        }

        if ($raffle->drawn_at) {
            throw new \RuntimeException('Çekiliş tamamlanmış.');
        }

        $tickets = max(1, $tickets);
        $participant = RaffleParticipant::firstOrNew([
            'raffle_id' => $raffle->id,
            'user_id' => $user->id,
        ]);

        $newTotal = ($participant->ticket_count ?? 0) + $tickets;
        if ($newTotal > $raffle->max_tickets_per_user) {
            throw new \RuntimeException('Maksimum bilet limitine ulaştınız.');
        }

        $cost = (float) $raffle->ticket_price * $tickets;
        if ($cost > 0) {
            $this->balance->debit($user, $cost, 'raffle_ticket', 'raffle:'.$raffle->id);
        }

        $participant->ticket_count = $newTotal;
        $participant->save();

        $this->xp->add($user, 'raffle_join');

        return $participant->fresh();
    }

    public function draw(Raffle $raffle): ?User
    {
        if ($raffle->drawn_at) {
            return User::find($raffle->winner_user_id);
        }

        $participants = RaffleParticipant::where('raffle_id', $raffle->id)->get();
        if ($participants->isEmpty()) {
            return null;
        }

        $pool = [];
        foreach ($participants as $p) {
            for ($i = 0; $i < $p->ticket_count; $i++) {
                $pool[] = $p->user_id;
            }
        }

        $winnerId = $pool[array_rand($pool)];
        $raffle->update([
            'winner_user_id' => $winnerId,
            'drawn_at' => now(),
            'is_active' => false,
        ]);

        $winner = User::find($winnerId);
        if ($winner) {
            $this->notifications->send($winner, 'Çekiliş kazandınız!', $raffle->title.' çekilişini kazandınız.');
        }

        return $winner;
    }

    public function drawExpired(): int
    {
        $count = 0;
        Raffle::where('is_active', true)
            ->whereNull('drawn_at')
            ->where('ends_at', '<=', now())
            ->each(function (Raffle $raffle) use (&$count) {
                if ($this->draw($raffle)) {
                    $count++;
                }
            });

        return $count;
    }
}
