<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketEvent;
use App\Models\TicketParticipation;
use App\Models\TicketRequest;
use App\Models\User;
use App\Models\UserSponsor;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketEventController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $event = TicketEvent::with('sponsor')->findOrFail($id);
        $user = $request->user('sanctum');

        return response()->json([
            'status' => 'success',
            'data' => $this->detailPayload($event, $user),
        ]);
    }

    public function leaderboard(int $id): JsonResponse
    {
        TicketEvent::findOrFail($id);

        $items = TicketParticipation::where('ticket_event_id', $id)
            ->with('user:id,username')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TicketParticipation $part) => [
                'id' => $part->id,
                'ticket_number' => $part->payload['ticket_number'] ?? str_pad((string) $part->id, 6, '0', STR_PAD_LEFT),
                'user' => [
                    'username' => $part->user?->username ?? '—',
                ],
            ])
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function adminRequests(int $id): JsonResponse
    {
        TicketEvent::findOrFail($id);

        $items = TicketRequest::where('ticket_event_id', $id)
            ->with('user:id,name,username,email')
            ->orderByDesc('id')
            ->get()
            ->map->toAdminApiArray()
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function request(Request $request, int $id, UploadService $upload): JsonResponse
    {
        $user = $request->user();
        $event = TicketEvent::findOrFail($id);

        if (! $event->is_active || $event->resolveStatus() === 'ended') {
            return response()->json(['message' => 'Etkinlik aktif değil.'], 422);
        }

        if ($event->sponsor_id && ! $this->hasSponsorLinked($user, $event)) {
            return response()->json(['message' => 'Önce sponsor hesabınızı bağlamanız gerekiyor.'], 422);
        }

        $request->validate([
            'investment_amount' => 'required|numeric|min:0',
            'screenshot' => 'required|file|image|max:10240',
        ]);

        $pending = TicketRequest::where('user_id', $user->id)
            ->where('ticket_event_id', $event->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json(['message' => 'Bu etkinlik için zaten bekleyen bir talebiniz var.'], 422);
        }

        $screenshotUrl = $upload->storeImage($request->file('screenshot'), 'ticket-requests');

        TicketRequest::create([
            'user_id' => $user->id,
            'ticket_event_id' => $event->id,
            'status' => 'pending',
            'payload' => [
                'investment_amount' => (float) $request->input('investment_amount'),
                'screenshot_url' => $screenshotUrl,
                'note' => $request->input('note', $request->input('description')),
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bilet talebiniz alındı. Onay bekleniyor.',
        ]);
    }

    /** @return array<string, mixed> */
    private function detailPayload(TicketEvent $event, ?User $user): array
    {
        $eventRow = $event->toDetailApiArray();

        $userTickets = collect();
        $userRequests = collect();

        if ($user) {
            $userTickets = TicketParticipation::where('ticket_event_id', $event->id)
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get()
                ->map(fn (TicketParticipation $part) => [
                    'id' => $part->id,
                    'ticket_number' => $part->payload['ticket_number'] ?? str_pad((string) $part->id, 6, '0', STR_PAD_LEFT),
                    'user' => ['username' => $user->username],
                    'created_at' => $part->created_at?->toISOString(),
                ]);

            $userRequests = TicketRequest::where('ticket_event_id', $event->id)
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get()
                ->map(fn (TicketRequest $req) => [
                    'id' => $req->id,
                    'investment_amount' => $req->payload['investment_amount'] ?? 0,
                    'status' => $req->status,
                    'approved_ticket_count' => $req->payload['approved_ticket_count'] ?? null,
                    'created_at' => $req->created_at?->toISOString(),
                    'user' => ['username' => $user->username],
                ]);
        }

        return [
            'event' => $eventRow,
            'has_sponsor_linked' => $user ? $this->hasSponsorLinked($user, $event) : false,
            'user_tickets' => $userTickets->values()->all(),
            'user_requests' => $userRequests->values()->all(),
            'winning_tickets' => $this->winningTickets($event),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function winningTickets(TicketEvent $event): array
    {
        return TicketParticipation::where('ticket_event_id', $event->id)
            ->where('payload->is_winner', true)
            ->with('user:id,username')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TicketParticipation $part) => [
                'id' => $part->id,
                'ticket_number' => $part->payload['ticket_number'] ?? str_pad((string) $part->id, 6, '0', STR_PAD_LEFT),
                'user' => ['username' => $part->user?->username ?? '—'],
            ])
            ->values()
            ->all();
    }

    private function hasSponsorLinked(User $user, TicketEvent $event): bool
    {
        if (! $event->sponsor_id) {
            return true;
        }

        return UserSponsor::where('user_id', $user->id)
            ->where('sponsor_id', $event->sponsor_id)
            ->exists();
    }
}
