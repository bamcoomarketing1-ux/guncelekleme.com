<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramSetting;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function show(): JsonResponse
    {
        $s = TelegramSetting::current();

        return response()->json(['status' => 'success', 'data' => [
            'bot_username' => $s->bot_username,
            'webhook_url' => $s->webhook_url,
            'is_active' => $s->is_active,
            'has_token' => ! empty($s->bot_token),
        ]]);
    }

    public function update(Request $request, TelegramService $telegram): JsonResponse
    {
        $s = TelegramSetting::current();
        $s->update($request->only(['bot_token', 'bot_username', 'webhook_url', 'is_active']));

        if ($s->bot_username) {
            $siteData = \App\Models\SiteSetting::current();
            $siteData['telegram_bot_username'] = ltrim($s->bot_username, '@');
            \App\Models\SiteSetting::query()->updateOrCreate(
                ['id' => 1],
                ['data' => \App\Models\SiteSetting::normalizeData($siteData)]
            );
        }

        if ($request->boolean('register_webhook', false)) {
            $result = $telegram->setWebhook();
            if (! $result['ok']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['description'],
                ], 422);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Telegram ayarları güncellendi.', 'data' => $s->fresh()]);
    }

    public function webhook(Request $request, TelegramService $telegram): JsonResponse
    {
        $telegram->processWebhook($request->all());

        return response()->json(['ok' => true]);
    }

    public function verificationLink(Request $request, TelegramService $telegram): JsonResponse
    {
        $link = $telegram->verificationLink($request->user());

        return response()->json(['status' => 'success', 'data' => ['link' => $link]]);
    }
}
