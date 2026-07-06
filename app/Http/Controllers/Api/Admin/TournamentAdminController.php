<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentAdminController extends Controller
{
    public function show(int $id): JsonResponse
    {
        return response()->json($this->payload(Tournament::findOrFail($id)));
    }

    public function addParticipant(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'slot' => 'required|integer|min:1',
        ]);

        $tournament = Tournament::findOrFail($id);
        $user = User::findOrFail($request->integer('user_id'));
        $participants = $tournament->participants ?? [];

        $participants = array_values(array_filter(
            $participants,
            fn (array $p) => ($p['slot'] ?? null) !== $request->integer('slot')
                && ($p['user']['id'] ?? $p['user_id'] ?? null) !== $user->id
        ));

        $participant = [
            'id' => count($participants) + 1,
            'slot' => $request->integer('slot'),
            'user' => ['id' => $user->id, 'username' => $user->username, 'name' => $user->name],
            'user_id' => $user->id,
        ];
        $participants[] = $participant;
        $tournament->update(['participants' => $participants]);

        return response()->json([
            'status' => 'success',
            'participant' => $participant,
            ...$this->payload($tournament->fresh()),
        ]);
    }

    public function removeParticipant(int $id, int $participantId): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $participants = array_values(array_filter(
            $tournament->participants ?? [],
            fn (array $p) => (int) ($p['id'] ?? 0) !== $participantId
        ));
        $tournament->update(['participants' => $participants]);

        return response()->json(['status' => 'success', ...$this->payload($tournament->fresh())]);
    }

    public function start(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $size = (int) ($tournament->size ?? 8);
        $matches = $this->buildInitialMatches($tournament->participants ?? [], $size);
        $tournament->update([
            'status' => 'active',
            'is_active' => true,
            'matches' => $matches,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Turnuva başlatıldı.', ...$this->payload($tournament->fresh())]);
    }

    public function setMatchWinner(Request $request, int $id, int $matchId): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $winnerId = $request->input('winner_id');
        $matches = $tournament->matches ?? [];

        foreach ($matches as &$match) {
            if ((int) ($match['id'] ?? 0) !== $matchId) {
                continue;
            }
            if ($winnerId === null || $winnerId === '') {
                unset($match['winner_id'], $match['winner']);
            } else {
                $match['winner_id'] = (int) $winnerId;
                $participant = collect($tournament->participants ?? [])->first(
                    fn (array $p) => (int) ($p['user']['id'] ?? $p['user_id'] ?? 0) === (int) $winnerId
                );
                $match['winner'] = $participant['user'] ?? ['id' => (int) $winnerId];
            }
            break;
        }
        unset($match);

        $status = $tournament->status ?? 'active';
        $winner = $tournament->winner;
        $final = collect($matches)->first(fn (array $m) => ($m['round'] ?? 1) === $this->finalRound((int) ($tournament->size ?? 8)));
        if ($final && ! empty($final['winner_id'])) {
            $status = 'completed';
            $winner = ['user' => $final['winner'] ?? ['id' => $final['winner_id']]];
        }

        $tournament->update(['matches' => $matches, 'status' => $status, 'winner' => $winner]);

        return response()->json(['status' => 'success', ...$this->payload($tournament->fresh())]);
    }

    public function updateMatch(Request $request, int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $matchId = $request->input('match_id');
        $matches = $tournament->matches ?? [];

        foreach ($matches as &$match) {
            if (($match['id'] ?? null) == $matchId || ($match['match_id'] ?? null) == $matchId) {
                $match = array_merge($match, $request->only([
                    'home_score', 'away_score', 'status', 'winner_id', 'scheduled_at',
                ]));
                break;
            }
        }
        unset($match);

        $tournament->update(['matches' => $matches]);

        return response()->json(['status' => 'success', ...$this->payload($tournament->fresh())]);
    }

    public function updateBracket(Request $request, int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $tournament->update($request->only(['matches', 'participants', 'is_active', 'status']));

        return response()->json(['status' => 'success', ...$this->payload($tournament->fresh())]);
    }

    /** @return array<string, mixed> */
    private function payload(Tournament $tournament): array
    {
        $detail = $tournament->toDetailApiArray();

        return [
            'tournament' => $detail['tournament'],
            'participants' => $detail['participants'],
            'matches' => $detail['matches'],
            'data' => $detail['tournament'],
        ];
    }

    /** @param list<array<string, mixed>> $participants */
    private function buildInitialMatches(array $participants, int $size): array
    {
        $roundSize = max(2, $size);
        $matchCount = (int) ($roundSize / 2);
        $matches = [];

        for ($i = 1; $i <= $matchCount; $i++) {
            $home = $participants[$i * 2 - 2] ?? null;
            $away = $participants[$i * 2 - 1] ?? null;
            $matches[] = [
                'id' => $i,
                'round' => 1,
                'home' => $home['user'] ?? null,
                'away' => $away['user'] ?? null,
                'home_id' => $home['user']['id'] ?? $home['user_id'] ?? null,
                'away_id' => $away['user']['id'] ?? $away['user_id'] ?? null,
                'status' => 'pending',
            ];
        }

        return $matches;
    }

    private function finalRound(int $size): int
    {
        $rounds = (int) log(max(2, $size), 2);

        return max(1, $rounds);
    }
}
