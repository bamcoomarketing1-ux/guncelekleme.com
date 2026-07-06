<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Http;

class SupportAiService
{
    public function reply(SupportMessage $userMessage): SupportMessage
    {
        $settings = SiteSetting::current();
        $prompt = $settings['support_system_prompt'] ?? 'Sen yardımcı bir destek asistanısın.';
        $siteName = $settings['site_name'] ?? 'Platform';
        $prompt = str_replace('{site_name}', $siteName, $prompt);

        $history = SupportMessage::where('user_id', $userMessage->user_id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->sender === 'user' ? 'user' : 'assistant',
                'content' => $m->message,
            ])
            ->values()
            ->all();

        $replyText = null;
        if ($settings['chat_enabled'] ?? true) {
            $replyText = $this->callLlm($prompt, $history);
        }
        $replyText ??= $this->fallbackReply($userMessage->message);

        return SupportMessage::create([
            'user_id' => $userMessage->user_id,
            'sender' => 'bot',
            'message' => $replyText,
        ]);
    }

    private function callLlm(string $systemPrompt, array $messages): ?string
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return null;
        }

        $payload = [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'max_tokens' => 300,
            'temperature' => 0.7,
        ];

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('choices.0.message.content');
    }

    private function fallbackReply(string $message): string
    {
        $lower = mb_strtolower($message);
        $routes = [
            'bonus' => '/bonuslar sayfasından tüm bonusları görebilirsiniz.',
            'çark' => '/daily-wheel sayfasından günlük çarkı çevirebilirsiniz.',
            'market' => '/market sayfasından ürün satın alabilirsiniz.',
            'şifre' => 'Şifre sıfırlama için giriş sayfasındaki "Şifremi Unuttum" bağlantısını kullanın.',
            'kayıt' => 'Kayıt olmak için ana sayfadaki kayıt formunu doldurun.',
        ];

        foreach ($routes as $keyword => $answer) {
            if (str_contains($lower, $keyword)) {
                return $answer;
            }
        }

        return 'Mesajınız alındı. Destek ekibimiz en kısa sürede size dönüş yapacaktır.';
    }
}
