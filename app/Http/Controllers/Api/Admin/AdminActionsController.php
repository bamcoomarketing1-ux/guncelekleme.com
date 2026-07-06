<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\BalanceTransaction;
use App\Models\Bonus;
use App\Models\Popup;
use App\Models\Promocode;
use App\Models\PromocodeUsage;
use App\Models\SpecialOdd;
use App\Models\SpecialOddBet;
use App\Models\TicketEvent;
use App\Models\TicketParticipation;
use App\Models\TicketRequest;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AdminActionsController extends Controller
{
    public function toggleAnnouncement(int $id): JsonResponse
    {
        $m = Announcement::findOrFail($id);
        $m->update(['is_active' => ! $m->is_active]);
        return response()->json(['status' => 'success', 'data' => $m->fresh()->toApiArray()]);
    }

    public function toggleBonusFeatured(int $id): JsonResponse
    {
        $m = Bonus::findOrFail($id);
        $m->update(['is_featured' => ! $m->is_featured]);
        return response()->json(['status' => 'success', 'data' => $m->fresh()->toApiArray()]);
    }

    public function togglePromocode(int $id): JsonResponse
    {
        $m = Promocode::findOrFail($id);
        $m->update(['is_active' => ! $m->is_active]);
        return response()->json(['status' => 'success', 'data' => $m->fresh()->toApiArray()]);
    }

    public function togglePopup(int $id): JsonResponse
    {
        $popup = Popup::findOrFail($id);
        $popup->update(['is_active' => ! $popup->is_active]);

        return response()->json(['status' => 'success', 'data' => $popup->fresh()->toApiArray()]);
    }

    public function promocodeUsages(int $id): JsonResponse
    {
        $rows = PromocodeUsage::where('promocode_id', $id)->with('user:id,username,email')->orderByDesc('id')->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function specialOddBets(int $id): JsonResponse
    {
        $rows = SpecialOddBet::where('special_odd_id', $id)->with('user:id,username,email')->orderByDesc('id')->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function settleSpecialOdd(Request $request, int $id): JsonResponse
    {
        $odd = SpecialOdd::findOrFail($id);
        $status = $request->input('status', 'won');
        $multiplier = (float) ($odd->odds ?? $odd->odd_value ?? 1);
        $odd->update([
            'status' => $status,
            'is_active' => false,
            'odds' => $multiplier,
            'odd_value' => $multiplier,
        ]);
        if ($status === 'won') {
            foreach (SpecialOddBet::where('special_odd_id', $id)->where('status', 'pending')->get() as $bet) {
                $payout = round((float) $bet->amount * $multiplier, 2);
                app(BalanceService::class)->adjust(User::find($bet->user_id), $payout, 'special_odd_win', 'odd:'.$id);
                $bet->update(['status' => 'won', 'payout' => $payout]);
            }
        } else {
            SpecialOddBet::where('special_odd_id', $id)->where('status', 'pending')->update(['status' => 'lost']);
        }

        return response()->json(['status' => 'success', 'message' => 'Bahis sonuçlandırıldı.', 'data' => $odd->fresh()->toApiArray()]);
    }

    public function toggleTicketEventHomepage(int $id): JsonResponse
    {
        $m = TicketEvent::findOrFail($id);
        $m->update(['show_on_homepage' => ! $m->show_on_homepage]);
        return response()->json(['status' => 'success', 'data' => $m->fresh()->toApiArray()]);
    }

    public function endTicketEvent(int $id): JsonResponse
    {
        $m = TicketEvent::findOrFail($id);
        $m->update(['status' => 'ended', 'is_active' => false]);
        return response()->json(['status' => 'success', 'message' => 'Etkinlik sonlandırıldı.', 'data' => $m->fresh()]);
    }

    public function approveTicketRequest(Request $httpRequest, int $id): JsonResponse
    {
        $ticketRequest = TicketRequest::findOrFail($id);

        if ($ticketRequest->status === 'approved') {
            return response()->json(['message' => 'Talep zaten onaylanmış.'], 422);
        }

        $payload = $ticketRequest->payload ?? [];
        $approvedCount = max(1, (int) $httpRequest->input('ticket_count', 1));

        $ticketRequest->update([
            'status' => 'approved',
            'payload' => array_merge($payload, ['approved_ticket_count' => $approvedCount]),
        ]);

        if ($ticketRequest->user_id && $ticketRequest->ticket_event_id) {
            for ($i = 0; $i < $approvedCount; $i++) {
                $partId = (TicketParticipation::max('id') ?? 0) + 1;
                TicketParticipation::create([
                    'user_id' => $ticketRequest->user_id,
                    'ticket_event_id' => $ticketRequest->ticket_event_id,
                    'status' => 'active',
                    'payload' => [
                        'ticket_number' => str_pad((string) $partId, 6, '0', STR_PAD_LEFT),
                        'from_request_id' => $ticketRequest->id,
                        'is_winner' => false,
                    ],
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Onaylandı.', 'data' => $ticketRequest->fresh()]);
    }

    public function rejectTicketRequest(Request $httpRequest, int $id): JsonResponse
    {
        $ticketRequest = TicketRequest::findOrFail($id);
        $payload = $ticketRequest->payload ?? [];

        $ticketRequest->update([
            'status' => 'rejected',
            'payload' => array_merge($payload, [
                'rejection_reason' => $httpRequest->input('reason'),
            ]),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Reddedildi.', 'data' => $ticketRequest->fresh()]);
    }

    public function toggleUserActive(int $id): JsonResponse
    {
        $u = User::findOrFail($id);
        $u->update(['is_active' => ! $u->is_active]);
        return response()->json(['status' => 'success', 'data' => $u->fresh()]);
    }

    public function toggleUserModerator(int $id): JsonResponse
    {
        $u = User::findOrFail($id);
        $u->update(['is_moderator' => ! $u->is_moderator]);
        return response()->json(['status' => 'success', 'data' => $u->fresh()]);
    }

    public function updateUserBalance(Request $request, int $id, BalanceService $balance, XpService $xp): JsonResponse
    {
        $u = User::findOrFail($id);
        $amount = (float) $request->input('balance', $request->input('amount', 0));
        $delta = $amount - (float) $u->balance;
        if ($delta != 0) {
            $balance->adjust($u, $delta, 'admin_adjust', 'admin:'.$request->user()->id);
        }
        $user = $u->fresh();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Bakiye güncellendi.',
            'data' => $xp->userApiPayload($user),
        ]);
    }

    public function updateUserXp(Request $request, int $id, XpService $xp): JsonResponse
    {
        $u = User::findOrFail($id);
        $u->update([
            'xp' => (int) $request->input('xp', $u->xp),
            'level' => (int) $request->input('level', $u->level),
        ]);
        $user = $u->fresh();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'XP ve seviye güncellendi.',
            'data' => $xp->userApiPayload($user),
        ]);
    }

    public function userHistory(int $id): JsonResponse
    {
        $rows = BalanceTransaction::where('user_id', $id)->orderByDesc('id')->limit(100)->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function disconnectTelegram(int $id): JsonResponse
    {
        $u = User::findOrFail($id);
        $u->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_first_name' => null,
            'telegram_verified_at' => null,
        ]);
        return response()->json(['status' => 'success', 'message' => 'Telegram bağlantısı kesildi.', 'data' => $u->fresh()]);
    }

    public function approveMarketOrder(int $id): JsonResponse
    {
        $order = \App\Models\MarketOrder::findOrFail($id);
        $order->update(['status' => 'approved']);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    public function rejectMarketOrder(int $id, BalanceService $balance): JsonResponse
    {
        $order = \App\Models\MarketOrder::with('product')->findOrFail($id);
        if ($order->status === 'pending') {
            $user = User::find($order->user_id);
            $product = $order->product ?? \App\Models\MarketProduct::find($order->market_product_id);
            if ($user && $product) {
                $balance->adjust($user, (float) $product->price, 'market_refund', 'order:'.$order->id);
            }
        }
        $order->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Sipariş reddedildi ve bakiye iade edildi.', 'data' => $order->fresh()]);
    }

    public function drawRaffle(int $id, \App\Services\RaffleService $raffles): JsonResponse
    {
        $raffle = \App\Models\Raffle::findOrFail($id);
        $winner = $raffles->draw($raffle);

        return response()->json([
            'status' => 'success',
            'message' => $winner ? 'Kazanan: '.$winner->username : 'Katılımcı yok.',
            'data' => $raffle->fresh()->load('winner'),
        ]);
    }
}
