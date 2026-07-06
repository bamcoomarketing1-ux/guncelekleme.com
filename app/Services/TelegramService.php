<?php

namespace App\Services;

use App\Models\TelegramSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function resolveWebhookUrl(?string $url = null): string
    {
        if ($url) {
            return $url;
        }

        $settings = TelegramSetting::current();
        if ($settings->webhook_url) {
            return $settings->webhook_url;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '' || str_contains($baseUrl, 'localhost')) {
            $baseUrl = rtrim((string) (request()?->getSchemeAndHttpHost() ?? ''), '/');
        }

        if ($baseUrl !== '' && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.preg_replace('#^https?://#', '', $baseUrl);
        }

        return $baseUrl.'/api/telegram/webhook';
    }

    /** @return array{ok: bool, description: string, webhook_url: string} */
    public function setWebhook(?string $url = null): array
    {
        $settings = TelegramSetting::current();
        $webhookUrl = $this->resolveWebhookUrl($url);

        if (! $settings->bot_token) {
            return [
                'ok' => false,
                'description' => 'Bot token kayıtlı değil. Önce Kaydet\'e basın veya token girin.',
                'webhook_url' => $webhookUrl,
            ];
        }

        if (! str_starts_with($webhookUrl, 'https://')) {
            return [
                'ok' => false,
                'description' => 'Webhook adresi HTTPS olmalı. .env içinde APP_URL=https://slotdeneme2025.com ayarlayın.',
                'webhook_url' => $webhookUrl,
            ];
        }

        $response = Http::timeout(30)->post("https://api.telegram.org/bot{$settings->bot_token}/setWebhook", [
            'url' => $webhookUrl,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);

        $body = $response->json();
        $ok = is_array($body) && ($body['ok'] ?? false) === true;

        if ($ok) {
            $settings->update(['webhook_url' => $webhookUrl]);

            return [
                'ok' => true,
                'description' => (string) ($body['description'] ?? 'Webhook is set'),
                'webhook_url' => $webhookUrl,
            ];
        }

        $description = is_array($body)
            ? (string) ($body['description'] ?? 'Telegram API hatası')
            : trim($response->body());

        if ($description === '') {
            $description = 'Telegram API yanıt vermedi. Sunucunun dış bağlantı (api.telegram.org) iznini kontrol edin.';
        }

        Log::warning('Telegram setWebhook failed', [
            'status' => $response->status(),
            'description' => $description,
            'webhook_url' => $webhookUrl,
        ]);

        return [
            'ok' => false,
            'description' => $description,
            'webhook_url' => $webhookUrl,
        ];
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        $token = TelegramSetting::current()->bot_token;
        if (! $token) {
            return false;
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        return $response->successful();
    }

    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));
        $from = $message['from'] ?? [];

        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text, 2);
            $verifyToken = $parts[1] ?? null;

            if ($verifyToken && str_starts_with($verifyToken, 'verify_')) {
                $userId = (int) str_replace('verify_', '', $verifyToken);
                $user = User::find($userId);
                if ($user) {
                    $this->linkTelegramUser($user, $chatId, $from);

                    return;
                }
            }

            $this->sendMessage($chatId, "Merhaba! Hesabınızı bağlamak için sitedeki profil sayfasından Telegram doğrulama kodunu kullanın.");
        }

        if (str_starts_with($text, '/verify')) {
            $parts = explode(' ', $text, 2);
            $code = strtoupper(trim($parts[1] ?? ''));
            if ($code === '') {
                $this->sendMessage($chatId, 'Lütfen doğrulama kodunuzu girin: /verify KODUNUZ');

                return;
            }

            $user = User::where('telegram_verification_code', $code)
                ->where(function ($query) {
                    $query->whereNull('telegram_verification_expires_at')
                        ->orWhere('telegram_verification_expires_at', '>', now());
                })
                ->first();

            if (! $user) {
                $this->sendMessage($chatId, '❌ Geçersiz veya süresi dolmuş doğrulama kodu.');

                return;
            }

            $this->linkTelegramUser($user, $chatId, $from);

            return;
        }
    }

    private function linkTelegramUser(User $user, string $chatId, array $from): void
    {
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $from['username'] ?? null,
            'telegram_first_name' => $from['first_name'] ?? null,
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null,
            'telegram_verification_expires_at' => null,
        ]);
        $this->sendMessage($chatId, "✅ Telegram hesabınız <b>{$user->username}</b> ile bağlandı.");
    }

    public function verificationLink(User $user): ?string
    {
        $settings = TelegramSetting::current();
        if (! $settings->is_active || ! $settings->bot_username) {
            return null;
        }

        return 'https://t.me/'.ltrim($settings->bot_username, '@').'?start=verify_'.$user->id;
    }

    public function processWebhook(array $payload): void
    {
        try {
            $this->handleUpdate($payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram webhook error: '.$e->getMessage());
        }
    }
}
