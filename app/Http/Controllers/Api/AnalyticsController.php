<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AnalyticsController extends Controller
{
    public function visit(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $userId = $request->user('sanctum')?->id;
        $visitorKey = $analytics->trackVisit($request, $userId);

        return $this->withVisitorCookie(['status' => 'success'], $visitorKey);
    }

    public function ping(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $userId = $request->user('sanctum')?->id;
        $visitorKey = $analytics->trackPresence($request, $userId);

        return $this->withVisitorCookie(['status' => 'success'], $visitorKey);
    }

    public function sponsorClick(Request $request, int $id, AnalyticsService $analytics): JsonResponse
    {
        $sponsor = Sponsor::where('is_active', true)->findOrFail($id);
        $userId = $request->user('sanctum')?->id;
        $analytics->trackSponsorClick($request, $sponsor, $userId);
        $visitorKey = $analytics->visitorKey($request);

        return $this->withVisitorCookie(['status' => 'success'], $visitorKey);
    }

    /** @param  array<string, mixed>  $payload */
    private function withVisitorCookie(array $payload, string $visitorKey): JsonResponse
    {
        return response()
            ->json($payload)
            ->withCookie(cookie(
                AnalyticsService::VISITOR_COOKIE,
                $visitorKey,
                60 * 24 * 365,
                '/',
                null,
                false,
                false,
                false,
                Cookie::SAMESITE_LAX
            ));
    }
}
