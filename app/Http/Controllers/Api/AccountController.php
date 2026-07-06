<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\MarketOrder;
use App\Models\MarketProduct;
use App\Models\Notification;
use App\Models\PromocodeUsage;
use App\Models\SpecialOddBet;
use App\Models\Sponsor;
use App\Models\TicketEvent;
use App\Models\TicketParticipation;
use App\Models\TicketRequest;
use App\Models\User;
use App\Models\UserSponsor;
use App\Services\AccountHistoryService;
use App\Services\BalanceService;
use App\Services\DeviceSessionService;
use App\Services\UploadService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    public function account(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function wallets(Request $request): JsonResponse
    {
        $u = $request->user();
        return response()->json(['data' => [
            'trc20' => $u->wallet_trc20,
            'erc20' => $u->wallet_erc20,
            'iban' => $u->wallet_iban,
        ]]);
    }

    public function updateWallets(Request $request): JsonResponse
    {
        $request->user()->update($request->only(['wallet_trc20', 'wallet_erc20', 'wallet_iban']));
        return response()->json(['status' => 'success', 'message' => 'Cüzdan bilgileri güncellendi.']);
    }

    public function history(Request $request, AccountHistoryService $history): JsonResponse
    {
        $type = (string) $request->get('type', 'all');
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, min(100, (int) $request->get('per_page', 20)));

        return response()->json($history->forUser($request->user(), $type, $page, $perPage));
    }

    public function notifications(Request $request): JsonResponse
    {
        $rows = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map->toApiArray();

        return response()->json(['data' => $rows]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->update(['is_read' => true]);
        return response()->json(['message' => 'Tüm bildirimler okundu.']);
    }

    public function tickets(Request $request): JsonResponse
    {
        $rows = TicketRequest::where('user_id', $request->user()->id)->get();
        return response()->json(['data' => $rows]);
    }

    public function sessions(Request $request, DeviceSessionService $deviceSessions): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $deviceSessions->refreshCurrentTokenMeta($current, $request);
        }

        return response()->json([
            'status' => 'success',
            'sessions' => $deviceSessions->listForUser($user, $current),
        ]);
    }

    public function promoHistory(Request $request): JsonResponse
    {
        $rows = PromocodeUsage::where('user_id', $request->user()->id)->with('promocode')->orderByDesc('id')->get();
        return response()->json(['data' => $rows]);
    }

    public function sponsors(Request $request): JsonResponse
    {
        $items = Sponsor::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['data' => $items]);
    }

    public function userSponsors(Request $request): JsonResponse
    {
        $user = $request->user();
        $linked = UserSponsor::where('user_id', $user->id)->get()->keyBy('sponsor_id');

        $items = Sponsor::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Sponsor $sponsor) use ($linked) {
                $row = $sponsor->toApiArray();
                $account = $linked->get($sponsor->id);
                $row['is_connected'] = $account !== null;
                $row['username'] = $account?->username;

                return $row;
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function connectUserSponsor(Request $request, int $id): JsonResponse
    {
        $request->validate(['username' => 'required|string|max:255']);
        $sponsor = Sponsor::where('is_active', true)->findOrFail($id);

        UserSponsor::updateOrCreate(
            ['user_id' => $request->user()->id, 'sponsor_id' => $sponsor->id],
            ['username' => trim($request->input('username'))]
        );

        return response()->json([
            'status' => 'success',
            'message' => "{$sponsor->name} hesabınız başarıyla kaydedildi.",
        ]);
    }

    public function participationHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $participations = TicketParticipation::where('user_id', $userId)
            ->with('ticketEvent')
            ->orderByDesc('id')
            ->get();

        $tickets = $participations->map(function (TicketParticipation $part) {
            $event = $part->ticketEvent;
            $payload = $part->payload ?? [];

            return [
                'id' => $part->id,
                'ticket_event_id' => $part->ticket_event_id,
                'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
                'ticket_number' => $payload['ticket_number'] ?? str_pad((string) $part->id, 6, '0', STR_PAD_LEFT),
                'created_at' => $part->created_at?->toISOString(),
                'is_winner' => (bool) ($payload['is_winner'] ?? false),
            ];
        })->values();

        $requests = TicketRequest::where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(function (TicketRequest $req) {
                $event = $req->ticket_event_id
                    ? TicketEvent::find($req->ticket_event_id)
                    : null;

                return [
                    'id' => $req->id,
                    'ticket_event_id' => $req->ticket_event_id,
                    'status' => $req->status,
                    'created_at' => $req->created_at?->toISOString(),
                    'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => $tickets,
                'requests' => $requests,
            ],
        ]);
    }

    public function telegramStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'verified' => $user->telegram_verified_at !== null,
            'telegram_username' => $user->telegram_username,
            'telegram_first_name' => $user->telegram_first_name,
            'telegram_verified_at' => $user->telegram_verified_at?->toISOString(),
        ]);
    }

    public function telegramGenerateCode(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->telegram_verified_at) {
            return response()->json(['message' => 'Telegram hesabınız zaten doğrulanmış.'], 422);
        }

        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $expiresAt = now()->addMinutes(15);

        $user->update([
            'telegram_verification_code' => $code,
            'telegram_verification_expires_at' => $expiresAt,
        ]);

        return response()->json([
            'code' => $code,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function verifyAccount(Request $request): JsonResponse
    {
        return app(AuthController::class)->verifyEmail($request);
    }

    public function uploadAvatar(Request $request, UploadService $upload, XpService $xp): JsonResponse
    {
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $request->validate(['avatar' => 'required|file|image|max:5120']);
            $avatar = $upload->storeImage($request->file('avatar'), 'avatars');
        } elseif ($request->filled('avatar')) {
            $preset = (int) $request->input('avatar');
            if ($preset < 1 || $preset > 10) {
                return response()->json(['message' => 'Geçersiz avatar seçimi.'], 422);
            }
            $avatar = (string) $preset;
        } else {
            return response()->json(['message' => 'Avatar gerekli.'], 422);
        }

        $user->update(['avatar' => $avatar]);
        $user->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar başarıyla güncellendi.',
            'user' => $xp->userApiPayload($user),
        ]);
    }

    public function readNotification(Request $request, int $id): JsonResponse
    {
        $n = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $n->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'data' => $n->fresh()->toApiArray()]);
    }

    public function deleteNotification(Request $request, int $id): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Silindi.']);
    }

    public function revokeSession(Request $request, int $id): JsonResponse
    {
        $current = $request->user()->currentAccessToken();
        if ($current && (int) $current->id === $id) {
            return response()->json(['message' => 'Aktif oturum sonlandırılamaz.'], 422);
        }

        PersonalAccessToken::where('tokenable_id', $request->user()->id)
            ->where('tokenable_type', User::class)
            ->where('id', $id)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Oturum sonlandırıldı.']);
    }

    public function revokeAllSessions(Request $request, DeviceSessionService $deviceSessions): JsonResponse
    {
        $current = $request->user()->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $deviceSessions->revokeOthers($request->user(), $current);
        }

        return response()->json(['status' => 'success', 'message' => 'Diğer oturumlar sonlandırıldı.']);
    }

    public function placeSpecialOddBet(Request $request, BalanceService $balance): JsonResponse
    {
        $oddId = $request->input('special_odd_id') ?? $request->input('event_id');
        if (! $oddId) {
            return response()->json(['message' => 'Etkinlik seçilmedi.'], 422);
        }

        $odd = \App\Models\SpecialOdd::where('is_active', true)->find($oddId);
        if (! $odd) {
            return response()->json(['message' => 'Bu etkinlik artık aktif değil.'], 422);
        }

        $user = $request->user();
        $amount = (float) ($request->input('amount') ?? $odd->bet_amount ?? 0);
        if ($amount <= 0) {
            return response()->json(['message' => 'Geçersiz bahis tutarı.'], 422);
        }

        if (SpecialOddBet::where('user_id', $user->id)->where('special_odd_id', $odd->id)->exists()) {
            return response()->json(['message' => 'Bu etkinliğe zaten katıldınız.'], 422);
        }

        $balance->debit($user, $amount, 'special_odd_bet', 'odd:'.$odd->id);
        $bet = SpecialOddBet::create([
            'user_id' => $user->id,
            'special_odd_id' => $odd->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bahis başarıyla oynandı!',
            'data' => $bet,
        ]);
    }

    public function mySpecialOddBets(Request $request): JsonResponse
    {
        $rows = SpecialOddBet::where('user_id', $request->user()->id)
            ->with(['specialOdd.league', 'specialOdd.homeTeam', 'specialOdd.awayTeam'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (SpecialOddBet $bet) => [
                'id' => $bet->id,
                'status' => $bet->status,
                'amount' => $bet->amount,
                'payout' => $bet->payout,
                'odds' => $bet->specialOdd?->odds ?? $bet->specialOdd?->odd_value,
                'created_at' => $bet->created_at?->toISOString(),
                'event' => $bet->specialOdd?->toApiArray(),
            ])
            ->values();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function marketOrder(Request $request, BalanceService $balance, XpService $xp): JsonResponse
    {
        $product = MarketProduct::findOrFail($request->input('product_id'));
        $user = $request->user();

        if (! $product->is_active) {
            return response()->json(['message' => 'Ürün satışta değil.'], 422);
        }

        $balance->debit($user, (float) $product->price, 'market_order', 'product:'.$product->id);
        $order = MarketOrder::create([
            'user_id' => $user->id,
            'market_product_id' => $product->id,
            'status' => 'pending',
            'payload' => array_merge($request->except(['product_id']), [
                'product' => $product->title,
                'price' => (float) $product->price,
                'wallet_details' => [
                    'trc20' => $user->wallet_trc20,
                    'erc20' => $user->wallet_erc20,
                    'iban' => $user->wallet_iban,
                ],
            ]),
        ]);
        $xp->add($user, 'market_order');

        return response()->json(['status' => 'success', 'data' => $order, 'message' => 'Sipariş oluşturuldu.']);
    }

    public function marketPurchase(Request $request, int $id, BalanceService $balance, XpService $xp): JsonResponse
    {
        $request->merge(['product_id' => $id]);
        $response = $this->marketOrder($request, $balance, $xp);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        $body = $response->getData(true);

        return response()->json([
            'status' => 'success',
            'message' => $body['message'] ?? 'Siparişiniz alındı. Onay bekleniyor.',
            'user' => $xp->userApiPayload($request->user()->fresh()),
        ]);
    }

    public function marketHistory(Request $request): JsonResponse
    {
        $items = MarketOrder::where('user_id', $request->user()->id)
            ->with('product')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MarketOrder $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'created_at' => $order->created_at?->toISOString(),
                'price_at_purchase' => $order->payload['price'] ?? $order->product?->price ?? 0,
                'product' => $order->product?->toApiArray(),
            ])
            ->values();

        return response()->json($items->all());
    }
}
