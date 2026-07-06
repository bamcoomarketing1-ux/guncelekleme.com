<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\GameSession;
use App\Models\PromocodeUsage;
use App\Models\ScratchCardPlay;
use App\Models\SpecialOddBet;
use App\Models\User;
use App\Models\WheelSpin;
use Illuminate\Support\Collection;

class AccountHistoryService
{
    /** @var array<string, string> */
    private const TYPE_LABELS = [
        'mines' => 'Mines',
        'dice' => 'Dice',
        'blackjack' => 'Blackjack',
        'market' => 'Market',
        'ticket' => 'Bilet',
        'promocode' => 'Promo Kod',
        'wheel' => 'Çark',
        'raffle' => 'Çekiliş',
        'scratch_card' => 'Kazı Kazan',
        'special_odds' => 'Özel Oran',
        'xp' => 'XP',
    ];

    /** @var array<string, list<string>> */
    private const BALANCE_TYPE_MAP = [
        'market' => ['market_order'],
        'ticket' => ['ticket_event'],
        'promocode' => ['promo'],
        'wheel' => ['wheel_spin', 'wheel_win'],
        'raffle' => ['raffle_ticket', 'raffle_win'],
        'scratch_card' => ['scratch_card_purchase', 'scratch_card_win'],
        'special_odds' => ['special_odd_bet', 'special_odd_win'],
        'xp' => ['xp_reward'],
    ];

    /**
     * @return array{data: list<array<string, mixed>>, summary: array<string, float|int>, pagination: array<string, int>}
     */
    public function forUser(User $user, string $type = 'all', int $page = 1, int $perPage = 20): array
    {
        $rows = $this->collectRows($user, $type)
            ->sortByDesc(fn (array $row) => $row['_sort_at'] ?? '')
            ->values();

        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $items = $rows->forPage($page, $perPage)
            ->map(fn (array $row) => collect($row)->except('_sort_at')->all())
            ->values()
            ->all();

        $profit = $rows->sum(fn (array $row) => (float) ($row['profit'] ?? 0));

        return [
            'data' => $items,
            'summary' => [
                'total' => $total,
                'profit' => round($profit, 2),
                'total_bet' => round($rows->sum(fn (array $row) => (float) ($row['bet_amount'] ?? 0)), 2),
                'total_win' => round($rows->sum(fn (array $row) => (float) ($row['win_amount'] ?? 0)), 2),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function collectRows(User $user, string $type): Collection
    {
        $rows = collect();

        if ($this->includesGameType($type)) {
            $rows = $rows->merge($this->gameRows($user, $type));
        }

        if ($this->includesBalanceType($type)) {
            $rows = $rows->merge($this->balanceRows($user, $type));
        }

        if ($type === 'all' || $type === 'promocode') {
            $rows = $rows->merge($this->promocodeRows($user));
        }

        if ($type === 'all' || $type === 'wheel') {
            $rows = $rows->merge($this->wheelRows($user));
        }

        if ($type === 'all' || $type === 'scratch_card') {
            $rows = $rows->merge($this->scratchRows($user));
        }

        if ($type === 'all' || $type === 'special_odds') {
            $rows = $rows->merge($this->specialOddRows($user));
        }

        return $rows;
    }

    private function includesGameType(string $type): bool
    {
        return $type === 'all' || in_array($type, ['mines', 'dice', 'blackjack'], true);
    }

    private function includesBalanceType(string $type): bool
    {
        if ($type === 'all') {
            return true;
        }

        return array_key_exists($type, self::BALANCE_TYPE_MAP);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function gameRows(User $user, string $type): Collection
    {
        $query = GameSession::where('user_id', $user->id)->whereIn('status', ['won', 'lost', 'active']);
        if ($type !== 'all') {
            $query->where('game', $type);
        }

        return $query->orderByDesc('id')->limit(500)->get()->map(function (GameSession $session) {
            $state = $session->state ?? [];
            $bet = (float) $session->bet;
            $win = (float) ($session->payout ?? 0);
            $status = $session->status === 'active' ? 'pending' : $session->status;

            return [
                'id' => $session->id,
                'type' => $session->game,
                'type_label' => self::TYPE_LABELS[$session->game] ?? ucfirst((string) $session->game),
                'date_formatted' => $session->created_at?->format('d/m H:i') ?? '',
                'bet_amount' => $bet,
                'win_amount' => $win,
                'multiplier' => (float) ($state['multiplier'] ?? ($bet > 0 && $win > 0 ? round($win / $bet, 2) : 0)),
                'detail' => $this->gameDetail($session),
                'profit' => round($win - $bet, 2),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'mines_count' => count($state['mine_cells'] ?? []),
                'opened_cells_count' => count($state['revealed'] ?? []),
                '_sort_at' => $session->created_at?->timestamp ?? 0,
            ];
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function balanceRows(User $user, string $type): Collection
    {
        $types = $type === 'all'
            ? collect(self::BALANCE_TYPE_MAP)->flatten()->unique()->values()->all()
            : (self::BALANCE_TYPE_MAP[$type] ?? []);

        if ($types === []) {
            return collect();
        }

        return BalanceTransaction::where('user_id', $user->id)
            ->whereIn('type', $types)
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function (BalanceTransaction $tx) {
                $mappedType = $this->mapBalanceType($tx->type);
                $amount = abs((float) $tx->amount);
                $isCredit = (float) $tx->amount > 0;

                return [
                    'id' => $tx->id,
                    'type' => $mappedType,
                    'type_label' => self::TYPE_LABELS[$mappedType] ?? ucfirst($mappedType),
                    'date_formatted' => $tx->created_at?->format('d/m H:i') ?? '',
                    'bet_amount' => $isCredit ? 0 : $amount,
                    'win_amount' => $isCredit ? $amount : 0,
                    'multiplier' => 0,
                    'detail' => $this->balanceDetail($tx),
                    'profit' => round((float) $tx->amount, 2),
                    'status' => $isCredit ? 'won' : 'participated',
                    'status_label' => $isCredit ? 'Kazanç' : 'Katılım',
                    '_sort_at' => $tx->created_at?->timestamp ?? 0,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function promocodeRows(User $user): Collection
    {
        return PromocodeUsage::where('user_id', $user->id)
            ->with('promocode')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (PromocodeUsage $usage) {
                $reward = (float) ($usage->promocode?->reward_amount ?? 0);

                return [
                    'id' => 'promo-'.$usage->id,
                    'type' => 'promocode',
                    'type_label' => self::TYPE_LABELS['promocode'],
                    'date_formatted' => $usage->created_at?->format('d/m H:i') ?? '',
                    'bet_amount' => 0,
                    'win_amount' => $reward,
                    'multiplier' => 0,
                    'detail' => $usage->promocode?->code ?? '-',
                    'code' => $usage->promocode?->code ?? '-',
                    'profit' => $reward,
                    'status' => 'won',
                    'status_label' => 'Kullanıldı',
                    '_sort_at' => $usage->created_at?->timestamp ?? 0,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function wheelRows(User $user): Collection
    {
        return WheelSpin::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (WheelSpin $spin) {
                $reward = (float) ($spin->reward ?? 0);

                return [
                    'id' => 'wheel-'.$spin->id,
                    'type' => 'wheel',
                    'type_label' => self::TYPE_LABELS['wheel'],
                    'date_formatted' => $spin->created_at?->format('d/m H:i') ?? '',
                    'bet_amount' => 0,
                    'win_amount' => $reward,
                    'multiplier' => 0,
                    'detail' => 'Günlük Çark',
                    'profit' => $reward,
                    'status' => $reward > 0 ? 'won' : 'lost',
                    'status_label' => $reward > 0 ? 'Kazandı' : 'Kaybetti',
                    '_sort_at' => $spin->created_at?->timestamp ?? 0,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function scratchRows(User $user): Collection
    {
        return ScratchCardPlay::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (ScratchCardPlay $play) {
                $reward = (float) ($play->reward_amount ?? 0);
                $status = $play->is_scratched ? ($reward > 0 ? 'won' : 'lost') : 'pending';

                return [
                    'id' => 'scratch-'.$play->id,
                    'type' => 'scratch_card',
                    'type_label' => self::TYPE_LABELS['scratch_card'],
                    'date_formatted' => $play->created_at?->format('d/m H:i') ?? '',
                    'bet_amount' => 0,
                    'win_amount' => $reward,
                    'multiplier' => 0,
                    'detail' => 'Kazı Kazan',
                    'profit' => $reward,
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    '_sort_at' => $play->created_at?->timestamp ?? 0,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function specialOddRows(User $user): Collection
    {
        return SpecialOddBet::where('user_id', $user->id)
            ->with('specialOdd')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (SpecialOddBet $bet) {
                $win = (float) ($bet->payout ?? 0);
                $amount = (float) $bet->amount;

                return [
                    'id' => 'odd-'.$bet->id,
                    'type' => 'special_odds',
                    'type_label' => self::TYPE_LABELS['special_odds'],
                    'date_formatted' => $bet->created_at?->format('d/m H:i') ?? '',
                    'bet_amount' => $amount,
                    'win_amount' => $win,
                    'multiplier' => (float) ($bet->specialOdd?->odds ?? $bet->specialOdd?->odd_value ?? 0),
                    'detail' => $bet->specialOdd?->prediction ?? $bet->specialOdd?->title ?? '-',
                    'profit' => round($win - $amount, 2),
                    'status' => $bet->status ?: 'pending',
                    'status_label' => $this->statusLabel($bet->status ?: 'pending'),
                    '_sort_at' => $bet->created_at?->timestamp ?? 0,
                ];
            });
    }

    private function mapBalanceType(string $type): string
    {
        foreach (self::BALANCE_TYPE_MAP as $mapped => $types) {
            if (in_array($type, $types, true)) {
                return $mapped;
            }
        }

        return 'market';
    }

    private function gameDetail(GameSession $session): string
    {
        $state = $session->state ?? [];

        return match ($session->game) {
            'mines' => count($state['revealed'] ?? []).' hücre',
            'dice' => 'Sonuç: '.($state['roll'] ?? '-'),
            'blackjack' => 'Skor: '.($state['user_score'] ?? '-'),
            default => '-',
        };
    }

    private function balanceDetail(BalanceTransaction $tx): string
    {
        if ($tx->type === 'market_order') {
            return 'Market siparişi';
        }

        return $tx->reference ?: '-';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'won' => 'Kazandı',
            'lost' => 'Kaybetti',
            'pending' => 'Beklemede',
            'participated' => 'Katıldı',
            'rejected' => 'Reddedildi',
            default => ucfirst($status),
        };
    }
}
