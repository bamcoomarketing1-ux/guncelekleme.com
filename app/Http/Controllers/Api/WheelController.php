<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use App\Services\BalanceService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WheelController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $prizes = WheelPrize::where('is_active', true)->orderBy('id')->get()->map(fn (WheelPrize $p) => $this->wheelItem($p));
        $lastSpin = ($user instanceof \App\Models\User)
            ? WheelSpin::where('user_id', $user->id)->whereDate('created_at', today())->latest()->first()
            : null;
        $canSpin = $user instanceof \App\Models\User && ! $lastSpin;
        $secondsRemaining = $canSpin ? 0 : ($user ? max(0, (int) now()->endOfDay()->diffInSeconds(now())) : 0);

        return response()->json([
            'items' => $prizes,
            'can_spin' => $canSpin,
            'seconds_remaining' => $secondsRemaining,
        ]);
    }

    public function userHistory(Request $request): JsonResponse
    {
        $rows = WheelSpin::where('user_id', $request->user()->id)
            ->with('prize')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (WheelSpin $spin) => [
                'id' => $spin->id,
                'item' => $spin->prize ? $this->wheelItem($spin->prize) : null,
                'reward_amount' => (float) ($spin->reward_amount ?: $spin->reward),
                'reward_type' => $spin->reward_type ?: 'balance',
                'created_at' => $spin->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function spinCompat(Request $request, BalanceService $balance, XpService $xp): JsonResponse
    {
        $response = $this->spin($request, $balance, $xp);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $payload = $response->getData(true);
        $user = $request->user()->fresh();
        $prize = $payload['data']['prize'] ?? [];
        $amount = (float) ($payload['data']['reward_amount'] ?? 0);

        return response()->json([
            'result' => [
                'item' => $prize,
                'reward_amount' => $amount,
                'reward_type' => $payload['data']['reward_type'] ?? 'balance',
                'user_new_balance' => (float) $user->balance,
            ],
        ]);
    }

    private function wheelItem(WheelPrize $prize): array
    {
        $row = $prize->toApiArray();

        return array_merge($row, [
            'item' => $row,
            'label' => $row['name'] ?? $prize->name,
            'amount' => (float) $prize->value,
        ]);
    }

    public function dailyWheel(?Request $request = null): JsonResponse
    {
        $user = $request?->user();
        $prizes = WheelPrize::where('is_active', true)->get()->map->toApiArray();
        $lastSpin = ($user instanceof \App\Models\User)
            ? WheelSpin::where('user_id', $user->id)->whereDate('created_at', today())->latest()->first()
            : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'can_spin' => ! $lastSpin,
                'next_spin_at' => $lastSpin ? today()->addDay()->startOfDay()->toIso8601String() : null,
                'prizes' => $prizes,
            ],
        ]);
    }

    public function spin(Request $request, BalanceService $balance, XpService $xp): JsonResponse
    {
        $user = $request->user();
        $todaySpins = WheelSpin::where('user_id', $user->id)->whereDate('created_at', today())->count();
        if ($todaySpins >= config('platform.limits.wheel_daily_spins', 1)) {
            return response()->json(['message' => 'Bugün zaten çevirdiniz.'], 422);
        }

        $prizes = WheelPrize::where('is_active', true)->get();
        if ($prizes->isEmpty()) {
            return response()->json(['message' => 'Ödül bulunamadı.'], 422);
        }

        $isCombo = $request->boolean('combo') && $user->level >= 5;
        $spinCount = $isCombo ? 2 : 1;
        $results = [];

        for ($i = 0; $i < $spinCount; $i++) {
            $pool = $prizes->flatMap(fn ($p) => array_fill(0, max(1, (int) $p->weight), $p->id))->all();
            $prize = $prizes->firstWhere('id', $pool[array_rand($pool)]);
            $amount = (float) $prize->value;
            $spin = WheelSpin::create([
                'user_id' => $user->id,
                'wheel_prize_id' => $prize->id,
                'reward' => $amount,
                'reward_amount' => $amount,
                'reward_type' => $prize->type,
                'is_combo_spin' => $isCombo,
            ]);
            if ($prize->type === 'balance' && $amount > 0) {
                $balance->adjust($user, $amount, 'wheel_spin', 'spin:'.$spin->id);
            }
            $results[] = [
                'prize' => $prize->toApiArray(),
                'reward_amount' => $amount,
                'reward_type' => $prize->type,
            ];
        }

        $xp->add($user, 'wheel_spin');

        return response()->json([
            'status' => 'success',
            'data' => [
                'prize' => $results[0]['prize'],
                'reward_amount' => array_sum(array_column($results, 'reward_amount')),
                'reward_type' => $results[0]['reward_type'],
                'is_combo_spin' => $isCombo,
                'spins' => $results,
            ],
        ]);
    }

    public function adminHistory(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $page = WheelSpin::with(['user', 'prize'])->orderByDesc('id')->paginate($perPage);
        $data = collect($page->items())->map(function (WheelSpin $spin) {
            return [
                'id' => $spin->id,
                'user_id' => $spin->user_id,
                'wheel_item_id' => $spin->wheel_prize_id,
                'reward_amount' => $spin->reward_amount ?: $spin->reward,
                'reward_type' => $spin->reward_type ?: 'balance',
                'is_combo_spin' => (bool) $spin->is_combo_spin,
                'created_at' => $spin->created_at,
                'updated_at' => $spin->updated_at,
                'user' => $spin->user,
                'wheel_item' => $spin->prize?->toApiArray(),
            ];
        });

        return response()->json([
            'current_page' => $page->currentPage(),
            'data' => $data,
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]);
    }
}
