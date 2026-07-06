<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LevelReward;
use App\Models\LinkItem;
use App\Models\MarketOrder;
use App\Models\ScratchCard;
use App\Models\ScratchCardPlay;
use App\Models\SiteSetting;
use App\Models\SupportMessage;
use App\Models\TelegramSetting;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminPanelCompatController extends Controller
{
    public function linkManagerIndex(): JsonResponse
    {
        $items = LinkItem::orderBy('sort_order')->get()->map(fn (LinkItem $item) => $item->toApiArray());

        return response()->json($items);
    }

    public function linkManagerBulkReplace(Request $request): JsonResponse
    {
        $request->validate([
            'find' => 'required|string',
            'replace' => 'nullable|string',
        ]);

        $find = $request->input('find');
        $replace = $request->input('replace', '');
        $count = 0;

        LinkItem::query()->get()->each(function (LinkItem $item) use ($find, $replace, &$count) {
            if (str_contains($item->url, $find)) {
                $item->update(['url' => str_replace($find, $replace, $item->url)]);
                $count++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "{$count} link güncellendi.",
            'updated' => $count,
        ]);
    }

    public function levelRewardsIndex(): JsonResponse
    {
        $items = LevelReward::orderBy('level')->get()->map->toApiArray();

        return response()->json($items);
    }

    public function levelRewardsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level' => 'required|integer|min:1',
            'reward_type' => 'required|string',
            'reward_amount' => 'required|numeric|min:0',
        ]);

        $reward = LevelReward::create($data);

        return response()->json($reward->toApiArray(), 201);
    }

    public function levelRewardsUpdate(Request $request, int $id): JsonResponse
    {
        $reward = LevelReward::findOrFail($id);
        $data = $request->validate([
            'level' => 'sometimes|integer|min:1',
            'reward_type' => 'sometimes|string',
            'reward_amount' => 'sometimes|numeric|min:0',
        ]);
        $reward->update($data);

        return response()->json($reward->fresh()->toApiArray());
    }

    public function levelRewardsDestroy(int $id): JsonResponse
    {
        LevelReward::findOrFail($id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Silindi.']);
    }

    public function marketOrders(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $paginator = MarketOrder::with(['user', 'product'])
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => collect($paginator->items())->map(fn (MarketOrder $order) => $order->toApiArray())->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function scratchSettingsShow(): JsonResponse
    {
        $settings = SiteSetting::query()->first();
        $data = $settings?->data ?? [];
        $stored = $data['scratch_card_settings'] ?? [];

        $cards = ScratchCard::orderBy('id')->get();
        $pool = $stored['reward_pool'] ?? $cards->map(fn (ScratchCard $card) => [
            'type' => 'balance',
            'chance' => (int) $card->weight,
            'amount_min' => (float) $card->reward_amount,
            'amount_max' => (float) $card->reward_amount,
            'label' => $card->title,
        ])->values()->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'price' => (int) ($stored['price'] ?? 50),
                'daily_limit' => (int) ($stored['daily_limit'] ?? config('platform.limits.scratch_daily_plays', 5)),
                'is_active' => (bool) ($stored['is_active'] ?? true),
                'reward_pool' => $pool,
            ],
        ]);
    }

    public function scratchSettingsUpdate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'price' => 'required|numeric|min:0',
            'daily_limit' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
            'reward_pool' => 'required|array',
        ]);

        $settings = SiteSetting::query()->firstOrCreate([]);
        $data = $settings->data ?? [];
        $data['scratch_card_settings'] = $payload;
        $settings->update(['data' => $data]);

        ScratchCard::query()->delete();
        foreach ($payload['reward_pool'] as $tier) {
            ScratchCard::create([
                'title' => $tier['label'] ?: 'Ödül',
                'reward_amount' => $tier['amount_max'] ?? $tier['amount_min'] ?? 0,
                'weight' => max(1, (int) ($tier['chance'] ?? 1)),
                'is_active' => true,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Ayarlar kaydedildi.', 'data' => $payload]);
    }

    public function scratchHistory(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $paginator = ScratchCardPlay::with(['user'])
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => collect($paginator->items())->map(function (ScratchCardPlay $play) {
                return [
                    'id' => $play->id,
                    'user_id' => $play->user_id,
                    'user' => $play->user ? [
                        'id' => $play->user->id,
                        'name' => $play->user->name,
                        'username' => $play->user->username,
                    ] : null,
                    'reward_amount' => (float) $play->reward_amount,
                    'created_at' => $play->created_at?->toISOString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function supportConversations(): JsonResponse
    {
        $rows = SupportMessage::query()
            ->whereNotNull('user_id')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        $users = User::whereIn('id', $rows->keys())->get()->keyBy('id');
        $conversations = $rows->map(function ($messages, $userId) use ($users) {
            $latest = $messages->first();
            $user = $users->get($userId);

            return [
                'user_id' => (int) $userId,
                'username' => $user?->username,
                'name' => $user?->name,
                'last_message' => $latest?->message,
                'message_count' => $messages->count(),
                'updated_at' => $latest?->created_at?->toISOString(),
            ];
        })->values()->sortByDesc('updated_at')->values();

        return response()->json($conversations);
    }

    public function supportConversation(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $messages = SupportMessage::where('user_id', $userId)->orderBy('id')->get()->map->toChatApiArray();

        return response()->json([
            'user' => ['id' => $user->id, 'username' => $user->username, 'name' => $user->name],
            'messages' => $messages,
        ]);
    }

    public function supportConversationDestroy(int $userId): JsonResponse
    {
        SupportMessage::where('user_id', $userId)->delete();

        return response()->json(['status' => 'success', 'message' => 'Konuşma silindi.']);
    }

    public function supportStats(): JsonResponse
    {
        $today = today();
        $hourly = SupportMessage::query()
            ->whereDate('created_at', $today)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => ['hour' => (int) $row->hour, 'count' => (int) $row->count])
            ->values();

        $topUsers = SupportMessage::query()
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $userMap = User::whereIn('id', $topUsers->pluck('user_id'))->get()->keyBy('id');

        return response()->json([
            'total_messages' => SupportMessage::count(),
            'today_messages' => SupportMessage::whereDate('created_at', $today)->count(),
            'total_users' => SupportMessage::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'today_users' => SupportMessage::whereNotNull('user_id')->whereDate('created_at', $today)->distinct('user_id')->count('user_id'),
            'top_users' => $topUsers->map(fn ($row) => [
                'user_id' => $row->user_id,
                'username' => $userMap->get($row->user_id)?->username,
                'count' => (int) $row->count,
            ])->values(),
            'hourly' => $hourly,
        ]);
    }

    public function telegramSettingsShow(): JsonResponse
    {
        $telegram = TelegramSetting::current();
        $site = SiteSetting::current();
        $webhookUrl = app(TelegramService::class)->resolveWebhookUrl();

        return response()->json([
            'telegram_bot_token' => $telegram->bot_token ?? '',
            'telegram_bot_username' => $telegram->bot_username ?? '',
            'site_url' => rtrim((string) config('app.url'), '/'),
            'webhook_url' => $webhookUrl,
            'verified_users_count' => User::whereNotNull('telegram_verified_at')->count(),
            'webhook_active' => ! empty($telegram->webhook_url),
            'telegram_webhook_active' => ! empty($telegram->webhook_url),
            'is_active' => (bool) $telegram->is_active,
            'telegram_channel' => $site['telegram_channel'] ?? null,
        ]);
    }

    public function telegramSettingsUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'telegram_bot_username' => 'nullable|string',
        ]);

        $telegram = TelegramSetting::current();
        $telegram->update([
            'bot_token' => $data['telegram_bot_token'] ?? $telegram->bot_token,
            'bot_username' => $data['telegram_bot_username'] ?? $telegram->bot_username,
        ]);

        if (! empty($telegram->bot_username)) {
            $siteData = SiteSetting::current();
            $siteData['telegram_bot_username'] = ltrim($telegram->bot_username, '@');
            SiteSetting::query()->updateOrCreate(['id' => 1], ['data' => SiteSetting::normalizeData($siteData)]);
        }

        return response()->json(['status' => 'success', 'message' => 'Telegram ayarları kaydedildi.']);
    }

    public function telegramSetWebhook(Request $request, TelegramService $telegram): JsonResponse
    {
        if ($request->filled('telegram_bot_token') || $request->filled('telegram_bot_username')) {
            $settings = TelegramSetting::current();
            $settings->update([
                'bot_token' => $request->input('telegram_bot_token', $settings->bot_token),
                'bot_username' => $request->input('telegram_bot_username', $settings->bot_username),
            ]);
        }

        $result = $telegram->setWebhook($request->input('webhook_url'));

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['description'],
                'webhook_url' => $result['webhook_url'],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook kuruldu.',
            'webhook_url' => $result['webhook_url'],
        ]);
    }

    public function telegramDeleteWebhook(): JsonResponse
    {
        $settings = TelegramSetting::current();
        if ($settings->bot_token) {
            Http::post("https://api.telegram.org/bot{$settings->bot_token}/deleteWebhook");
        }
        $settings->update(['webhook_url' => null]);

        return response()->json(['status' => 'success', 'message' => 'Webhook kaldırıldı.']);
    }

    public function telegramBroadcast(Request $request, TelegramService $telegram): JsonResponse
    {
        $request->validate(['message' => 'required|string']);
        $message = $request->input('message');
        $sent = 0;

        User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')->unique()->each(function ($chatId) use ($telegram, $message, &$sent) {
            if ($telegram->sendMessage((string) $chatId, $message)) {
                $sent++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "{$sent} kullanıcıya duyuru gönderildi.",
            'sent' => $sent,
        ]);
    }
}
