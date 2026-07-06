<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScratchCard;
use App\Models\ScratchCardPlay;
use App\Models\SiteSetting;
use App\Services\BalanceService;
use App\Services\GameLimitService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScratchCardController extends Controller
{
    public function showPublic(Request $request, GameLimitService $limits): JsonResponse
    {
        $user = $request->user();
        $setting = $this->scratchSettings();
        $todayCount = $limits->scratchPlaysToday($user->id);
        $dailyLimit = (int) ($setting['daily_limit'] ?? config('platform.limits.scratch_daily_plays', 5));
        $price = (float) ($setting['price'] ?? 0);
        $canPurchase = ($setting['is_active'] ?? true)
            && $todayCount < $dailyLimit
            && (float) $user->balance >= $price
            && ! ScratchCardPlay::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->where('is_scratched', false)
                ->exists();

        $todayCard = ScratchCardPlay::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('is_scratched', false)
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'setting' => $setting,
                'today_count' => $todayCount,
                'can_purchase' => $canPurchase,
                'today_card' => $todayCard ? $this->playToCard($todayCard) : null,
            ],
        ]);
    }

    public function purchase(Request $request, BalanceService $balance, GameLimitService $limits): JsonResponse
    {
        $user = $request->user();
        $setting = $this->scratchSettings();

        if (! ($setting['is_active'] ?? true)) {
            return response()->json(['message' => 'Kazı kazan şu an aktif değil.'], 422);
        }

        $dailyLimit = (int) ($setting['daily_limit'] ?? config('platform.limits.scratch_daily_plays', 5));
        if ($limits->scratchPlaysToday($user->id) >= $dailyLimit) {
            return response()->json(['message' => 'Günlük kazı kazan limitine ulaştınız.'], 422);
        }

        if (ScratchCardPlay::where('user_id', $user->id)->whereDate('created_at', today())->where('is_scratched', false)->exists()) {
            return response()->json(['message' => 'Açmadığınız bir kartınız var.'], 422);
        }

        $price = (float) ($setting['price'] ?? 0);
        if ($price > 0) {
            $balance->debit($user, $price, 'scratch_card_purchase', 'scratch:purchase');
        }

        $cards = ScratchCard::where('is_active', true)->get();
        if ($cards->isEmpty()) {
            return response()->json(['message' => 'Kart bulunamadı.'], 422);
        }

        $pool = $cards->flatMap(fn ($c) => array_fill(0, max(1, (int) $c->weight), $c->id))->all();
        $card = $cards->firstWhere('id', $pool[array_rand($pool)]);
        $amount = (float) $card->reward_amount;

        $play = ScratchCardPlay::create([
            'user_id' => $user->id,
            'scratch_card_id' => $card->id,
            'reward_amount' => $amount,
            'reward_type' => 'balance',
            'is_scratched' => false,
            'payload' => ['card' => $card->toApiArray()],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => ['card_id' => $play->id],
        ]);
    }

    public function reveal(Request $request, int $id, BalanceService $balance, XpService $xp): JsonResponse
    {
        $play = ScratchCardPlay::where('user_id', $request->user()->id)->findOrFail($id);

        if ($play->is_scratched) {
            return response()->json(['message' => 'Bu kart zaten açılmış.'], 422);
        }

        $play->update(['is_scratched' => true]);

        $amount = (float) $play->reward_amount;
        if ($amount > 0 && ($play->reward_type ?: 'balance') === 'balance') {
            $balance->adjust($request->user(), $amount, 'scratch_card', 'card:'.$play->id);
        }
        $xp->add($request->user(), 'scratch_card');

        return response()->json([
            'status' => 'success',
            'data' => [
                'reward_type' => $play->reward_type ?: 'balance',
                'reward_amount' => $amount,
            ],
        ]);
    }

    private function scratchSettings(): array
    {
        $stored = SiteSetting::current()['scratch_card_settings'] ?? [];
        $cards = ScratchCard::where('is_active', true)->orderBy('id')->get();
        $pool = $stored['reward_pool'] ?? $cards->map(fn (ScratchCard $card) => [
            'type' => 'balance',
            'chance' => (int) $card->weight,
            'amount_min' => (float) $card->reward_amount,
            'amount_max' => (float) $card->reward_amount,
            'label' => $card->title,
        ])->values()->all();

        return [
            'price' => (int) ($stored['price'] ?? 50),
            'daily_limit' => (int) ($stored['daily_limit'] ?? config('platform.limits.scratch_daily_plays', 5)),
            'is_active' => (bool) ($stored['is_active'] ?? true),
            'reward_pool' => $pool,
        ];
    }

    private function playToCard(ScratchCardPlay $play): array
    {
        return [
            'id' => $play->id,
            'is_scratched' => (bool) $play->is_scratched,
            'reward_amount' => (float) $play->reward_amount,
            'reward_type' => $play->reward_type ?: 'balance',
            'card' => $play->payload['card'] ?? $play->scratchCard?->toApiArray(),
        ];
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => ScratchCard::orderBy('id')->get()->map->toApiArray()]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $card = ScratchCard::create($request->only(['title', 'description', 'reward_amount', 'weight', 'is_active']));

        return response()->json(['status' => 'success', 'data' => $card->toApiArray()], 201);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        $card = ScratchCard::findOrFail($id);
        $card->update($request->only(['title', 'description', 'reward_amount', 'weight', 'is_active']));

        return response()->json(['status' => 'success', 'data' => $card->fresh()->toApiArray()]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        ScratchCard::findOrFail($id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Silindi.']);
    }

    public function play(Request $request, BalanceService $balance, GameLimitService $limits, XpService $xp): JsonResponse
    {
        try {
            $limits->assertScratchAllowed($request->user()->id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $cards = ScratchCard::where('is_active', true)->get();
        if ($cards->isEmpty()) {
            return response()->json(['message' => 'Kart bulunamadı.'], 422);
        }

        $pool = $cards->flatMap(fn ($c) => array_fill(0, max(1, (int) $c->weight), $c->id))->all();
        $card = $cards->firstWhere('id', $pool[array_rand($pool)]);
        $amount = (float) $card->reward_amount;

        ScratchCardPlay::create([
            'user_id' => $request->user()->id,
            'scratch_card_id' => $card->id,
            'reward_amount' => $amount,
        ]);

        if ($amount > 0) {
            $balance->adjust($request->user(), $amount, 'scratch_card', 'card:'.$card->id);
        }
        $xp->add($request->user(), 'scratch_card');

        return response()->json([
            'status' => 'success',
            'data' => [
                'card' => $card->toApiArray(),
                'reward' => $amount,
                'plays_today' => $limits->scratchPlaysToday($request->user()->id),
            ],
        ]);
    }

    public function dailyStats(Request $request, GameLimitService $limits): JsonResponse
    {
        return response()->json(['data' => $limits->dailyStats($request->user()->id, 'scratch')]);
    }
}
