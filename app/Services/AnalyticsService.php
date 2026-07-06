<?php

namespace App\Services;

use App\Models\SitePresence;
use App\Models\SiteVisit;
use App\Models\Sponsor;
use App\Models\SponsorClick;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsService
{
    public const VISITOR_COOKIE = 'alisulasyon_vid';

    public const ONLINE_WINDOW_MINUTES = 5;

    public function visitorKey(Request $request): string
    {
        $existing = $request->cookie(self::VISITOR_COOKIE);
        if (is_string($existing) && strlen($existing) >= 16) {
            return $existing;
        }

        return Str::uuid()->toString();
    }

    public function trackVisit(Request $request, ?int $userId = null): string
    {
        $visitorKey = $this->visitorKey($request);

        SiteVisit::create([
            'visitor_key' => $visitorKey,
            'user_id' => $userId,
            'path' => '/'.ltrim((string) $request->input('path', $request->path()), '/'),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'referrer' => Str::limit((string) $request->headers->get('referer', ''), 500, ''),
        ]);

        $this->touchPresence($request, $visitorKey, $userId);

        return $visitorKey;
    }

    public function trackPresence(Request $request, ?int $userId = null): string
    {
        $visitorKey = $this->visitorKey($request);
        $this->touchPresence($request, $visitorKey, $userId);

        return $visitorKey;
    }

    public function trackSponsorClick(Request $request, Sponsor $sponsor, ?int $userId = null): void
    {
        $visitorKey = $this->visitorKey($request);

        SponsorClick::create([
            'sponsor_id' => $sponsor->id,
            'user_id' => $userId,
            'visitor_key' => $visitorKey,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'referrer' => Str::limit((string) $request->headers->get('referer', ''), 500, ''),
        ]);

        $this->touchPresence($request, $visitorKey, $userId);
    }

    public function onlineUsersCount(): int
    {
        return SitePresence::where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES))->count();
    }

    public function uniqueVisitorsBetween(Carbon $start, Carbon $end): int
    {
        return SiteVisit::whereBetween('created_at', [$start, $end])
            ->distinct('visitor_key')
            ->count('visitor_key');
    }

    public function totalPageViewsBetween(Carbon $start, Carbon $end): int
    {
        return SiteVisit::whereBetween('created_at', [$start, $end])->count();
    }

    public function sponsorClicksBetween(Carbon $start, Carbon $end): int
    {
        return SponsorClick::whereBetween('created_at', [$start, $end])->count();
    }

    /** @return list<array{id:int,name:string,clicks:int}> */
    public function sponsorClickBreakdown(Carbon $start, Carbon $end, int $limit = 10): array
    {
        return SponsorClick::query()
            ->select('sponsor_id', DB::raw('COUNT(*) as clicks'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('sponsor_id')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $sponsor = Sponsor::find($row->sponsor_id);

                return [
                    'id' => (int) $row->sponsor_id,
                    'name' => $sponsor?->name ?? 'Sponsor #'.$row->sponsor_id,
                    'clicks' => (int) $row->clicks,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, int|list<array<string, mixed>>> */
    public function summaryForPeriod(int $periodDays): array
    {
        $end = now()->endOfDay();
        $start = now()->subDays(max(1, $periodDays) - 1)->startOfDay();
        $todayStart = now()->startOfDay();

        return [
            'online_now' => $this->onlineUsersCount(),
            'visitors_today' => $this->uniqueVisitorsBetween($todayStart, $end),
            'visitors_period' => $this->uniqueVisitorsBetween($start, $end),
            'page_views_period' => $this->totalPageViewsBetween($start, $end),
            'sponsor_clicks_today' => SponsorClick::where('created_at', '>=', $todayStart)->count(),
            'sponsor_clicks_period' => $this->sponsorClicksBetween($start, $end),
            'sponsor_clicks_breakdown' => $this->sponsorClickBreakdown($start, $end),
        ];
    }

    private function touchPresence(Request $request, string $visitorKey, ?int $userId): void
    {
        SitePresence::updateOrCreate(
            ['visitor_key' => $visitorKey],
            [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'last_seen_at' => now(),
            ]
        );
    }
}
