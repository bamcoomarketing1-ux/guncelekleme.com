<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketEvent;
use App\Models\TicketParticipation;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketParticipationController extends Controller
{
    public function adminIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $page = TicketParticipation::with(['user', 'ticketEvent'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => collect($page->items())->map->toAdminTicketArray()->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function adminEventParticipations(int $eventId): JsonResponse
    {
        TicketEvent::findOrFail($eventId);

        $items = TicketParticipation::where('ticket_event_id', $eventId)
            ->whereNotNull('user_id')
            ->with('user:id,name,username,email,avatar')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($parts, $userId) {
                /** @var \Illuminate\Support\Collection<int, TicketParticipation> $parts */
                $user = $parts->first()?->user;

                return [
                    'user_id' => (int) $userId,
                    'user' => TicketParticipation::userNestedArray($user),
                    'ticket_count' => $parts->count(),
                    'has_winner' => $parts->contains(fn (TicketParticipation $part) => $part->isWinner()),
                ];
            })
            ->values()
            ->sortByDesc('ticket_count')
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function adminUserTickets(int $eventId, int $userId): JsonResponse
    {
        TicketEvent::findOrFail($eventId);
        User::findOrFail($userId);

        $items = TicketParticipation::where('ticket_event_id', $eventId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map->toAdminTicketArray()
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function searchUser(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['message' => 'En az 2 karakter girin.'], 422);
        }

        $user = User::query()
            ->where('username', $query)
            ->orWhere('email', $query)
            ->orWhere('username', 'like', '%'.$query.'%')
            ->orWhere('email', 'like', '%'.$query.'%')
            ->first();

        if (! $user) {
            return response()->json(null);
        }

        $participations = TicketParticipation::where('user_id', $user->id)
            ->with('ticketEvent:id,title,status,is_active')
            ->orderByDesc('id')
            ->get()
            ->groupBy('ticket_event_id')
            ->map(function ($parts) {
                /** @var \Illuminate\Support\Collection<int, TicketParticipation> $parts */
                $event = $parts->first()?->ticketEvent;
                $status = $event?->resolveStatus() ?? 'ended';

                return [
                    'event_id' => $event?->id,
                    'event_title' => $event?->title ?? '—',
                    'event_status' => $status,
                    'ticket_count' => $parts->count(),
                    'has_winner' => $parts->contains(fn (TicketParticipation $part) => $part->isWinner()),
                ];
            })
            ->values();

        return response()->json([
            'user' => TicketParticipation::userNestedArray($user),
            'participations' => $participations,
        ]);
    }

    public function searchTicket(Request $request): JsonResponse
    {
        $number = trim((string) $request->input('number', ''));
        if ($number === '') {
            return response()->json(['message' => 'Bilet numarası girin.'], 422);
        }

        $part = TicketParticipation::with(['user:id,name,username,avatar', 'ticketEvent:id,title'])
            ->where(function ($q) use ($number) {
                $q->where('payload->ticket_number', $number);
                if (is_numeric($number)) {
                    $q->orWhere('id', (int) $number);
                }
            })
            ->orderByDesc('id')
            ->first();

        if (! $part) {
            return response()->json(null);
        }

        return response()->json($part->toSearchTicketArray());
    }

    public function addTickets(Request $request, int $eventId, int $userId): JsonResponse
    {
        $request->validate(['count' => 'required|integer|min:1|max:100']);
        $event = TicketEvent::findOrFail($eventId);
        User::findOrFail($userId);

        $created = [];
        $count = (int) $request->input('count');

        DB::transaction(function () use ($event, $userId, $count, &$created) {
            for ($i = 0; $i < $count; $i++) {
                $partId = (TicketParticipation::max('id') ?? 0) + 1;
                $created[] = TicketParticipation::create([
                    'user_id' => $userId,
                    'ticket_event_id' => $event->id,
                    'status' => 'active',
                    'payload' => [
                        'ticket_number' => str_pad((string) $partId, 6, '0', STR_PAD_LEFT),
                        'is_winner' => false,
                    ],
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'data' => collect($created)->map->toAdminTicketArray()->values(),
        ]);
    }

    public function removeTickets(Request $request, int $eventId, int $userId): JsonResponse
    {
        $request->validate(['count' => 'required|integer|min:1|max:100']);
        TicketEvent::findOrFail($eventId);
        User::findOrFail($userId);

        $count = (int) $request->input('count');
        $removed = TicketParticipation::where('ticket_event_id', $eventId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (TicketParticipation $part) => ! $part->isWinner())
            ->take($count);

        $deletedIds = [];
        foreach ($removed as $part) {
            $deletedIds[] = $part->id;
            $part->delete();
        }

        return response()->json([
            'status' => 'success',
            'removed' => count($deletedIds),
            'data' => ['deleted_ids' => $deletedIds],
        ]);
    }

    public function deleteTicket(int $eventId, int $ticketId): JsonResponse
    {
        $part = TicketParticipation::where('ticket_event_id', $eventId)->findOrFail($ticketId);
        $part->delete();

        return response()->json(['status' => 'success']);
    }

    public function join(Request $request, BalanceService $balance): JsonResponse
    {
        $request->validate(['ticket_event_id' => 'required|integer|exists:ticket_events,id']);
        $event = TicketEvent::findOrFail($request->ticket_event_id);

        if (! $event->is_active || $event->resolveStatus() === 'ended') {
            return response()->json(['message' => 'Etkinlik aktif değil.'], 422);
        }

        $existing = TicketParticipation::where('user_id', $request->user()->id)
            ->where('ticket_event_id', $event->id)
            ->exists();
        if ($existing) {
            return response()->json(['message' => 'Bu etkinliğe zaten katıldınız.'], 422);
        }

        $price = (float) ($event->ticket_price ?? 0);
        if ($price > 0) {
            $balance->debit($request->user(), $price, 'ticket_event', 'event:'.$event->id);
        }

        $partId = (TicketParticipation::max('id') ?? 0) + 1;
        $part = TicketParticipation::create([
            'user_id' => $request->user()->id,
            'ticket_event_id' => $event->id,
            'status' => 'active',
            'payload' => array_merge($request->except(['ticket_event_id']), [
                'ticket_number' => str_pad((string) $partId, 6, '0', STR_PAD_LEFT),
                'is_winner' => false,
            ]),
        ]);

        return response()->json(['status' => 'success', 'data' => $part->toAdminTicketArray()]);
    }
}
