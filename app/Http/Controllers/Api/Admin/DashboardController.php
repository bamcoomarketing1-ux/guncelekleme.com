<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\MarketOrder;
use App\Models\PromocodeUsage;
use App\Models\Raffle;
use App\Models\RaffleParticipant;
use App\Models\SponsorClick;
use App\Models\SpecialOddBet;
use App\Models\TicketRequest;
use App\Models\User;
use App\Models\WheelSpin;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->has('period')) {
            return $this->statistics($request);
        }

        $recent = User::orderByDesc('id')->limit(8)->get(['id', 'username', 'email', 'balance', 'level', 'created_at']);
        $pendingOrders = MarketOrder::where('status', 'pending')->orderByDesc('id')->limit(5)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => $this->baseStats(),
                'recent_users' => $recent,
                'pending_orders' => $pendingOrders->map(fn ($o) => [
                    'id' => $o->id,
                    'user' => User::find($o->user_id)?->username,
                    'product' => $o->payload['product'] ?? null,
                    'created_at' => $o->created_at?->diffForHumans(),
                ]),
                'pending_tickets' => TicketRequest::where('status', 'pending')->limit(5)->get(),
            ],
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $period = max(1, (int) $request->input('period', 30));
        $end = now()->endOfDay();
        $start = now()->subDays($period - 1)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $start->copy()->subDays($period)->startOfDay();

        $labels = [];
        $logins = [];
        $registrations = [];
        for ($i = 0; $i < $period; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format($period <= 30 ? 'd M' : 'M Y');
            $registrations[] = User::whereDate('created_at', $day)->count();
            $logins[] = $this->loginCountForDay($day);
        }

        $activeThis = $this->activeUsersBetween($start, $end);
        $activePrev = $this->activeUsersBetween($prevStart, $prevEnd);
        $newThis = User::whereBetween('created_at', [$start, $end])->count();
        $newPrev = User::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => array_merge([
                    'total_users' => User::count(),
                    'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                    'new_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
                    'active_this_period' => $activeThis,
                    'active_prev_period' => $activePrev,
                    'new_this_period' => $newThis,
                    'new_prev_period' => $newPrev,
                    'session_active_period' => GameSession::whereBetween('created_at', [$start, $end])->count(),
                ], $this->analytics->summaryForPeriod($period)),
                'charts' => [
                    'labels' => $labels,
                    'logins' => $logins,
                    'registrations' => $registrations,
                ],
            ],
        ]);
    }

    /** @return array<string, int|float> */
    private function baseStats(): array
    {
        return [
            'total_users' => User::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'total_games' => GameSession::count(),
            'games_today' => GameSession::whereDate('created_at', today())->count(),
            'pending_market_orders' => MarketOrder::where('status', 'pending')->count(),
            'pending_ticket_requests' => TicketRequest::where('status', 'pending')->count(),
            'active_raffles' => Raffle::where('is_active', true)->count(),
            'total_raffle_participants' => RaffleParticipant::sum('ticket_count'),
            'wheel_spins_today' => WheelSpin::whereDate('created_at', today())->count(),
            'wheel_spins_total' => WheelSpin::count(),
            'promo_usages_today' => PromocodeUsage::whereDate('created_at', today())->count(),
            'special_odds_bets_today' => SpecialOddBet::whereDate('created_at', today())->count(),
            'online_now' => $this->analytics->onlineUsersCount(),
            'visitors_today' => $this->analytics->uniqueVisitorsBetween(now()->startOfDay(), now()->endOfDay()),
            'sponsor_clicks_today' => SponsorClick::where('created_at', '>=', now()->startOfDay())->count(),
        ];
    }

    private function loginCountForDay(Carbon $day): int
    {
        if (Schema::hasColumn('users', 'last_login_at')) {
            return User::whereDate('last_login_at', $day)->count();
        }

        return GameSession::whereDate('created_at', $day)->distinct('user_id')->count('user_id');
    }

    private function activeUsersBetween(Carbon $start, Carbon $end): int
    {
        if (Schema::hasColumn('users', 'last_login_at')) {
            return User::whereBetween('last_login_at', [$start, $end])->count();
        }

        return GameSession::whereBetween('created_at', [$start, $end])->distinct('user_id')->count('user_id');
    }
}
