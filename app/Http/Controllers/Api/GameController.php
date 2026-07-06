<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\SiteSetting;
use App\Services\GameLimitService;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function minesConfig(): JsonResponse
    {
        $settings = SiteSetting::current();

        return response()->json(['data' => [
            'min_bet' => 1,
            'max_bet' => 10000,
            'max_daily_plays' => config('platform.limits.mines_daily_plays', 100),
            'background_image' => $settings['background_image'] ?? null,
            'games_background_image' => $settings['background_image'] ?? null,
        ]]);
    }

    public function diceConfig(): JsonResponse
    {
        return response()->json(['data' => [
            'min_bet' => 1,
            'max_bet' => 10000,
            'max_daily_plays' => config('platform.limits.dice_daily_plays', 100),
        ]]);
    }

    public function blackjackConfig(): JsonResponse
    {
        return response()->json(['data' => ['min_bet' => 1, 'max_bet' => 10000]]);
    }

    public function minesActive(Request $request, GameService $games): JsonResponse
    {
        return response()->json($games->activeMines($request->user()));
    }

    public function blackjackActive(Request $request, GameService $games): JsonResponse
    {
        $game = $games->activeBlackjack($request->user());

        return response()->json([
            'status' => 'success',
            'game' => $game,
        ]);
    }

    public function minesDailyStats(Request $request, GameService $games): JsonResponse
    {
        return response()->json($games->dailyStatsPayload($request->user()->id, 'mines'));
    }

    public function diceDailyStats(Request $request, GameService $games): JsonResponse
    {
        return response()->json($games->dailyStatsPayload($request->user()->id, 'dice'));
    }

    public function startMines(Request $request, GameService $games): JsonResponse
    {
        try {
            $bet = (float) $request->input('bet_amount', $request->input('bet', 10));
            $mines = (int) $request->input('mines_count', $request->input('mines', 5));

            return response()->json($games->startMines($request->user(), $bet, $mines));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function revealMines(Request $request, GameService $games): JsonResponse
    {
        try {
            return response()->json($games->revealMines(
                $request->user(),
                (int) $request->input('game_id', 0),
                (int) $request->input('cell_index', $request->input('cell', 0))
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aktif oyun bulunamadı.'], 404);
        }
    }

    public function cashoutMines(Request $request, GameService $games): JsonResponse
    {
        try {
            $gameId = (int) $request->input('game_id', 0);

            return response()->json($games->cashoutMines(
                $request->user(),
                $gameId > 0 ? $gameId : null
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aktif oyun bulunamadı.'], 404);
        }
    }

    public function playDice(Request $request, GameService $games): JsonResponse
    {
        try {
            return response()->json($games->playDice(
                $request->user(),
                (float) $request->input('bet_amount', $request->input('bet', 10)),
                (int) $request->input('target', 50),
                (string) $request->input('direction', 'over')
            ));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function startBlackjack(Request $request, GameService $games): JsonResponse
    {
        return response()->json($games->startBlackjack(
            $request->user(),
            (float) $request->input('bet_amount', $request->input('bet', 10))
        ));
    }

    public function playBlackjack(Request $request, GameService $games): JsonResponse
    {
        return $this->startBlackjack($request, $games);
    }

    public function hitBlackjack(Request $request, GameService $games): JsonResponse
    {
        try {
            return response()->json($games->hitBlackjack($request->user(), (int) $request->input('game_id')));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aktif oyun bulunamadı.'], 404);
        }
    }

    public function standBlackjack(Request $request, GameService $games): JsonResponse
    {
        try {
            return response()->json($games->standBlackjack($request->user(), (int) $request->input('game_id')));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aktif oyun bulunamadı.'], 404);
        }
    }

    public function doubleBlackjack(Request $request, GameService $games): JsonResponse
    {
        try {
            return response()->json($games->doubleBlackjack($request->user(), (int) $request->input('game_id')));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aktif oyun bulunamadı.'], 404);
        }
    }

    public function allSessions(Request $request): JsonResponse
    {
        $rows = GameSession::where('user_id', $request->user()->id)->orderByDesc('id')->limit(100)->get();

        return response()->json(['data' => $rows]);
    }
}
