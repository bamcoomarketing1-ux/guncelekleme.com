<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Bonus;
use App\Models\MusicTrack;
use App\Models\NewsPost;
use App\Models\Popup;
use App\Models\Raffle;
use App\Models\MarketProduct;
use App\Models\Slider;
use App\Models\SpecialOdd;
use App\Models\Sponsor;
use App\Models\SocialMedia;
use App\Models\TicketEvent;
use App\Models\Tournament;
use App\Models\TrialBonus;
use App\Models\User;
use App\Models\LinkItem;
use App\Models\XpReward;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function banners(): JsonResponse
    {
        $grouped = Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Banner $b) => $this->normalizeBannerPosition($b->position))
            ->map(fn ($items) => $items->map->toApiArray()->values()->all())
            ->all();

        if (! isset($grouped['tv'])) {
            $grouped['tv'] = [];
        }

        return response()->json(['banners' => $grouped]);
    }

    private function normalizeBannerPosition(?string $position): string
    {
        $position = trim((string) $position);
        if ($position === '') {
            return 'homepage';
        }
        if (str_contains(mb_strtolower($position), 'tv')) {
            return 'tv';
        }

        return $position;
    }

    public function sliders(): JsonResponse
    {
        $items = Slider::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['sliders' => $items]);
    }

    public function sponsors(): JsonResponse
    {
        $items = Sponsor::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toApiArray();

        return response()->json(['sponsors' => $items]);
    }

    public function socialMedia(Request $request): JsonResponse
    {
        $items = SocialMedia::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function bonuses(Request $request): JsonResponse
    {
        $q = Bonus::with('sponsor')->where('is_active', true)->orderBy('sort_order');
        if ($request->has('featured')) {
            $q->where('is_featured', true);
        }

        return response()->json(['status' => 'success', 'data' => $q->get()->map->toApiArray()]);
    }

    public function trialBonuses(): JsonResponse
    {
        $items = TrialBonus::with('sponsor')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function announcementsBanner(): JsonResponse
    {
        $items = Announcement::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function popup(): JsonResponse
    {
        $items = Popup::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function market(): JsonResponse
    {
        $items = MarketProduct::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json($items->values()->all());
    }

    public function raffles(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $items = Raffle::where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Raffle $raffle) => $raffle->toPublicApiArray($user));

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function raffleShow(Request $request, int $id): JsonResponse
    {
        $raffle = Raffle::with('winner')->findOrFail($id);
        $user = $request->user('sanctum');
        $isParticipated = $user
            ? $raffle->participants()->where('user_id', $user->id)->exists()
            : false;

        return response()->json([
            'data' => [
                'raffle' => $raffle->toPublicApiArray($user),
                'is_participated' => $isParticipated,
                'winners' => $raffle->winnersList(),
            ],
        ]);
    }

    public function tournaments(): JsonResponse
    {
        $items = Tournament::where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Tournament $t) => $t->toListApiArray());

        return response()->json($items->values()->all());
    }

    public function tournament(int $id): JsonResponse
    {
        $t = Tournament::findOrFail($id);

        return response()->json($t->toDetailApiArray());
    }

    public function ticketEvents(Request $request): JsonResponse
    {
        $q = TicketEvent::with('sponsor')->orderByDesc('id');
        if ($request->is('api/ticket-events/homepage')) {
            $q->where('is_active', true)->where('show_on_homepage', true);
        }
        $items = $q->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function specialOdds(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $items = SpecialOdd::with(['league', 'homeTeam', 'awayTeam'])
            ->withCount('bets')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->map(fn (SpecialOdd $odd) => $odd->toApiArrayForUser($user));

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function specialOddsHistory(): JsonResponse
    {
        $items = SpecialOdd::with(['league', 'homeTeam', 'awayTeam'])
            ->where(function ($query) {
                $query->where('is_active', false)
                    ->orWhereIn('status', ['won', 'lost', 'settled', 'ended']);
            })
            ->orderByDesc('id')
            ->get()
            ->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function linkItems(): JsonResponse
    {
        $items = LinkItem::where('is_active', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function xpRewards(): JsonResponse
    {
        $items = XpReward::where('is_active', true)->orderBy('action')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function news(Request $request): JsonResponse
    {
        $perPage = max(1, (int) $request->get('per_page', 20));
        $page = max(1, (int) $request->get('page', 1));
        $q = NewsPost::where('is_active', true)->orderBy('sort_order')->orderByDesc('id');
        $total = $q->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = $q->forPage($page, $perPage)->get()->map->toApiArray();

        return response()->json([
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
            ],
        ]);
    }

    public function newsShow(string $slug): JsonResponse
    {
        $post = NewsPost::where('is_active', true)
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->firstOrFail();

        return response()->json($post->toApiArray());
    }

    public function music(): JsonResponse
    {
        $items = MusicTrack::where('is_active', true)->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function bonusesFeatured(): JsonResponse
    {
        $items = Bonus::where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->get()->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function socialMediaHomepage(): JsonResponse
    {
        $items = SocialMedia::where('is_active', true)
            ->where('show_on_homepage', true)
            ->orderBy('sort_order')
            ->get()
            ->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function ticketEventsHomepage(): JsonResponse
    {
        $items = TicketEvent::with('sponsor')
            ->where('is_active', true)
            ->where('show_on_homepage', true)
            ->get()
            ->map->toApiArray();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function dailyWheelPublic(): JsonResponse
    {
        return app(WheelController::class)->dailyWheel(request());
    }

    public function leaderboard(Request $request, XpService $xp): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $page = max(1, (int) $request->get('page', 1));
        $q = User::query()->orderByDesc('xp')->orderByDesc('balance');
        $total = $q->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = $q->forPage($page, $perPage)
            ->get(['id', 'username', 'name', 'avatar', 'xp', 'level', 'balance'])
            ->map(function (User $user) use ($xp) {
                $row = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'level' => (int) $user->level,
                    'balance' => number_format((float) $user->balance, 2, '.', ''),
                ];

                return array_merge($row, $xp->progressFields($user));
            });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
        ]);
    }
}
