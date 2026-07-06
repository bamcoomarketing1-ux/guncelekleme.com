<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Models\RaffleParticipant;
use App\Services\RaffleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RaffleController extends Controller
{
    public function join(Request $request, RaffleService $raffles): JsonResponse
    {
        $request->validate([
            'raffle_id' => 'required|integer|exists:raffles,id',
            'tickets' => 'integer|min:1|max:10',
        ]);

        return $this->joinRaffle($request->user(), (int) $request->input('raffle_id'), $raffles, (int) $request->input('tickets', 1));
    }

    public function joinById(Request $request, int $id, RaffleService $raffles): JsonResponse
    {
        $request->validate([
            'tickets' => 'integer|min:1|max:10',
        ]);

        return $this->joinRaffle($request->user(), $id, $raffles, (int) $request->input('tickets', 1));
    }

    private function joinRaffle($user, int $raffleId, RaffleService $raffles, int $tickets): JsonResponse
    {
        try {
            $raffle = Raffle::findOrFail($raffleId);
            $part = $raffles->join($user, $raffle, $tickets);

            return response()->json(['status' => 'success', 'data' => $part, 'message' => 'Çekilişe katıldınız.']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function myTickets(Request $request): JsonResponse
    {
        $rows = RaffleParticipant::where('user_id', $request->user()->id)
            ->with('raffle')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
