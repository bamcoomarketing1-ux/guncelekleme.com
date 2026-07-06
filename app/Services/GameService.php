<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\User;

class GameService
{
    private const SUITS = ['H', 'D', 'C', 'S'];

    private const MINES_STEP = 0.15;

    public function __construct(
        private BalanceService $balance,
        private GameLimitService $limits,
        private XpService $xp,
    ) {}

    public function startMines(User $user, float $bet, int $mines = 5): array
    {
        $this->limits->assertMinesAllowed($user->id);
        $this->balance->debit($user, $bet, 'mines_bet');
        $grid = range(0, 24);
        shuffle($grid);
        $mineCells = array_slice($grid, 0, $mines);
        $session = GameSession::create([
            'user_id' => $user->id,
            'game' => 'mines',
            'bet' => $bet,
            'status' => 'active',
            'state' => ['mine_cells' => $mineCells, 'revealed' => [], 'multiplier' => 1.0],
        ]);

        return [
            'status' => 'success',
            'game' => $this->formatMinesGame($session),
        ];
    }

    public function revealMines(User $user, int $gameId, int $cell): array
    {
        $session = $this->minesSession($user, $gameId);
        $state = $session->state;

        if ($session->status !== 'active') {
            throw new \RuntimeException('Aktif oyun bulunamadı.');
        }

        if (in_array($cell, $state['revealed'], true)) {
            return [
                'status' => 'success',
                'game' => $this->minesGamePayload($session),
            ];
        }

        if (in_array($cell, $state['mine_cells'], true)) {
            $session->update(['status' => 'lost']);

            return [
                'status' => 'lost',
                'mines' => $state['mine_cells'],
                'game' => $this->minesGamePayload($session->fresh()),
            ];
        }

        $state['revealed'][] = $cell;
        $state['multiplier'] = round(1 + count($state['revealed']) * self::MINES_STEP, 2);
        $session->update(['state' => $state]);

        return [
            'status' => 'success',
            'game' => $this->minesGamePayload($session->fresh()),
        ];
    }

    public function cashoutMines(User $user, ?int $gameId = null): array
    {
        $session = null;
        if ($gameId && $gameId > 0) {
            $session = GameSession::where('user_id', $user->id)
                ->where('game', 'mines')
                ->where('status', 'active')
                ->find($gameId);
        }
        if (! $session) {
            $session = GameSession::where('user_id', $user->id)
                ->where('game', 'mines')
                ->where('status', 'active')
                ->firstOrFail();
        }

        $state = $session->state;
        $mult = (float) ($state['multiplier'] ?? 1);
        $payout = round((float) $session->bet * $mult, 2);
        $this->balance->adjust($user, $payout, 'mines_win', 'game:'.$session->id);
        $session->update(['status' => 'won', 'payout' => $payout]);
        $this->xp->add($user, 'mines_win');

        return [
            'status' => 'won',
            'win_amount' => $payout,
            'multiplier' => $mult,
            'mines' => $state['mine_cells'] ?? [],
        ];
    }

    public function playDice(User $user, float $bet, int $target, string $direction = 'over'): array
    {
        $this->limits->assertDiceAllowed($user->id);
        $this->balance->debit($user, $bet, 'dice_bet');
        $roll = random_int(1, 100);
        $won = $direction === 'over' ? $roll > $target : $roll < $target;
        $payout = 0;
        if ($won) {
            $payout = round($bet * 1.95, 2);
            $this->balance->adjust($user, $payout, 'dice_win');
            $this->xp->add($user, 'dice_win');
        }
        GameSession::create([
            'user_id' => $user->id,
            'game' => 'dice',
            'bet' => $bet,
            'payout' => $payout,
            'status' => $won ? 'won' : 'lost',
            'state' => compact('roll', 'target', 'direction'),
        ]);

        return [
            'status' => 'success',
            'game' => [
                'result' => $roll,
                'status' => $won ? 'won' : 'lost',
                'win_amount' => $payout,
            ],
        ];
    }

    public function startBlackjack(User $user, float $bet): array
    {
        $this->balance->debit($user, $bet, 'blackjack_bet');
        $deck = $this->freshDeck();
        $player = [$this->draw($deck), $this->draw($deck)];
        $dealer = [$this->draw($deck), $this->draw($deck)];
        $session = GameSession::create([
            'user_id' => $user->id,
            'game' => 'blackjack',
            'bet' => $bet,
            'status' => 'active',
            'state' => ['deck' => $deck, 'player' => $player, 'dealer' => $dealer],
        ]);

        return [
            'status' => 'success',
            'game' => $this->formatBlackjackGame($session->fresh()),
        ];
    }

    public function hitBlackjack(User $user, int $gameId): array
    {
        $session = $this->bjSession($user, $gameId);
        $state = $session->state;
        $state['player'][] = $this->draw($state['deck']);
        $total = $this->handTotal($state['player']);
        if ($total > 21) {
            $session->update(['status' => 'lost', 'state' => $state]);
        } else {
            $session->update(['state' => $state]);
        }

        return [
            'status' => 'success',
            'game' => $this->formatBlackjackGame($session->fresh()),
        ];
    }

    public function standBlackjack(User $user, int $gameId): array
    {
        return $this->resolveBlackjack($this->bjSession($user, $gameId));
    }

    public function doubleBlackjack(User $user, int $gameId): array
    {
        $session = $this->bjSession($user, $gameId);
        $this->balance->debit($user, (float) $session->bet, 'blackjack_double');
        $session->update(['bet' => (float) $session->bet * 2]);
        $state = $session->fresh()->state;
        $state['player'][] = $this->draw($state['deck']);
        $session->update(['state' => $state]);

        return $this->resolveBlackjack($session->fresh());
    }

    public function activeMines(User $user): array
    {
        $session = GameSession::where('user_id', $user->id)
            ->where('game', 'mines')
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return ['active' => false];
        }

        return [
            'active' => true,
            'game' => $this->formatMinesGame($session),
        ];
    }

    public function activeBlackjack(User $user): ?array
    {
        $session = GameSession::where('user_id', $user->id)
            ->where('game', 'blackjack')
            ->where('status', 'active')
            ->first();

        return $session ? $this->formatBlackjackGame($session) : null;
    }

    public function dailyStatsPayload(int $userId, string $game): array
    {
        $stats = $this->limits->dailyStats($userId, $game);

        return [
            'remaining' => max(0, $stats['max_plays'] - $stats['plays_today']),
            'daily_limit' => $stats['max_plays'],
        ];
    }

    private function minesSession(User $user, int $gameId): GameSession
    {
        if ($gameId > 0) {
            $session = GameSession::where('user_id', $user->id)
                ->where('game', 'mines')
                ->where('status', 'active')
                ->find($gameId);
            if ($session) {
                return $session;
            }
        }

        return GameSession::where('user_id', $user->id)
            ->where('game', 'mines')
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function bjSession(User $user, int $gameId): GameSession
    {
        return GameSession::where('user_id', $user->id)
            ->where('game', 'blackjack')
            ->where('status', 'active')
            ->findOrFail($gameId);
    }

    private function resolveBlackjack(GameSession $session): array
    {
        $user = User::findOrFail($session->user_id);
        $state = $session->state;
        while ($this->handTotal($state['dealer']) < 17) {
            $state['dealer'][] = $this->draw($state['deck']);
        }
        $playerTotal = $this->handTotal($state['player']);
        $dealerTotal = $this->handTotal($state['dealer']);
        $result = 'lose';
        $payout = 0;
        if ($playerTotal <= 21 && ($dealerTotal > 21 || $playerTotal > $dealerTotal)) {
            $result = 'win';
            $payout = round((float) $session->bet * 2, 2);
            $this->balance->adjust($user, $payout, 'blackjack_win', 'game:'.$session->id);
            $this->xp->add($user, 'blackjack_win');
        } elseif ($playerTotal === $dealerTotal && $playerTotal <= 21) {
            $result = 'push';
            $payout = (float) $session->bet;
            $this->balance->adjust($user, $payout, 'blackjack_push', 'game:'.$session->id);
        }
        $session->update([
            'status' => $result === 'win' ? 'won' : ($result === 'push' ? 'push' : 'lost'),
            'payout' => $payout,
            'state' => $state,
        ]);

        return [
            'status' => 'success',
            'game' => $this->formatBlackjackGame($session->fresh(), hideDealerHole: false),
        ];
    }

    private function formatMinesGame(GameSession $session): array
    {
        return $this->minesGamePayload($session);
    }

    private function minesGamePayload(GameSession $session): array
    {
        $state = $session->state ?? [];
        $revealed = $state['revealed'] ?? [];
        $multiplier = (float) ($state['multiplier'] ?? 1);
        $mineCount = count($state['mine_cells'] ?? []);

        return [
            'id' => $session->id,
            'bet_amount' => (float) $session->bet,
            'mines_count' => $mineCount,
            'opened_cells' => $revealed,
            'multiplier' => $multiplier,
            'next_multiplier' => round($multiplier + self::MINES_STEP, 2),
            'status' => $this->mapSessionStatus($session->status),
        ];
    }

    private function formatBlackjackGame(GameSession $session, bool $hideDealerHole = true): array
    {
        $state = $session->state ?? [];
        $player = $state['player'] ?? [];
        $dealer = $state['dealer'] ?? [];
        $dealerHand = [];

        foreach ($dealer as $index => $card) {
            if ($hideDealerHole && $session->status === 'active' && $index === 1) {
                $dealerHand[] = 'hidden';
            } else {
                $dealerHand[] = $this->formatCard($card);
            }
        }

        $visibleDealer = $hideDealerHole && $session->status === 'active' && isset($dealer[0])
            ? [$dealer[0]]
            : $dealer;

        return [
            'id' => $session->id,
            'status' => $this->mapBlackjackStatus($session->status),
            'bet_amount' => (float) $session->bet,
            'user_hand' => $this->formatHand($player),
            'dealer_hand' => $dealerHand,
            'user_score' => $this->handTotal($player),
            'dealer_score' => $this->handTotal($visibleDealer),
            'multiplier' => 2,
            'win_amount' => (float) ($session->payout ?? 0),
        ];
    }

    private function mapSessionStatus(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'won' => 'won',
            'lost' => 'lost',
            default => $status,
        };
    }

    private function mapBlackjackStatus(string $status): string
    {
        return match ($status) {
            'active' => 'in_progress',
            'won' => 'won',
            'lost' => 'lost',
            'push' => 'push',
            default => $status,
        };
    }

    private function freshDeck(): array
    {
        $deck = [];
        foreach (self::SUITS as $suit) {
            for ($value = 1; $value <= 13; $value++) {
                $deck[] = ['suit' => $suit, 'value' => $value];
            }
        }
        shuffle($deck);

        return $deck;
    }

    private function draw(array &$deck): array
    {
        return array_pop($deck);
    }

    private function handTotal(array $hand): int
    {
        $total = 0;
        $aces = 0;
        foreach ($hand as $card) {
            $value = is_array($card) ? $card['value'] : $card;
            $v = $value === 1 ? 11 : min($value, 10);
            $total += $v;
            if ($value === 1) {
                $aces++;
            }
        }
        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }

        return $total;
    }

    private function formatHand(array $hand): array
    {
        return array_map(fn ($c) => $this->formatCard($c), $hand);
    }

    private function formatCard(array|int|string $card): string|array
    {
        if ($card === 'hidden') {
            return 'hidden';
        }

        if (is_string($card)) {
            return $card;
        }

        if (! is_array($card)) {
            return 'H-'.(string) $card;
        }

        $labels = [1 => 'A', 11 => 'J', 12 => 'Q', 13 => 'K'];
        $v = (int) $card['value'];
        $label = $labels[$v] ?? (string) $v;
        $suit = $card['suit'] ?? 'H';

        return $suit.'-'.$label;
    }
}
