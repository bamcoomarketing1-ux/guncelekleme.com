<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceSessionService
{
    public function createUserToken(User $user, Request $request): NewAccessToken
    {
        return $user->createToken($this->tokenNameFromRequest($request));
    }

    public function tokenNameFromRequest(Request $request): string
    {
        $meta = $this->parseUserAgent($request->userAgent() ?? '');
        $meta['ip'] = $request->ip() ?? '-';
        $meta['location'] = $this->guessLocation($request->ip());

        return json_encode($meta, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user, ?PersonalAccessToken $current = null): array
    {
        $currentId = $current?->id;

        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PersonalAccessToken $token) => $this->formatToken($token, $token->id === $currentId))
            ->values()
            ->all();
    }

    public function refreshCurrentTokenMeta(PersonalAccessToken $token, Request $request): void
    {
        if (! in_array($token->name, ['user', 'admin'], true)) {
            return;
        }

        $token->update(['name' => $this->tokenNameFromRequest($request)]);
    }

    public function revokeOthers(User $user, PersonalAccessToken $current): int
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where('id', '!=', $current->id)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatToken(PersonalAccessToken $token, bool $isCurrent): array
    {
        $meta = $this->decodeTokenName($token->name);
        $lastAt = $token->last_used_at ?? $token->created_at;

        return [
            'id' => $token->id,
            'is_current' => $isCurrent,
            'device_type' => $meta['device_type'] ?? 'desktop',
            'os' => $meta['os'] ?? 'Bilinmeyen Cihaz',
            'browser' => $meta['browser'] ?? 'Bilinmeyen Tarayıcı',
            'ip' => $meta['ip'] ?? '-',
            'location' => $meta['location'] ?? 'Bilinmiyor',
            'last_active' => $lastAt
                ? $lastAt->locale('tr')->translatedFormat('j F Y, H:i')
                : '-',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function decodeTokenName(string $name): array
    {
        if (in_array($name, ['user', 'admin'], true)) {
            return [];
        }

        $decoded = json_decode($name, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{device_type: string, os: string, browser: string}
     */
    private function parseUserAgent(string $ua): array
    {
        $lower = strtolower($ua);
        $deviceType = (str_contains($lower, 'mobile')
            || str_contains($lower, 'android')
            || str_contains($lower, 'iphone'))
            ? 'mobile'
            : 'desktop';

        $os = 'Bilinmeyen';
        if (str_contains($lower, 'windows')) {
            $os = 'Windows';
        } elseif (str_contains($lower, 'android')) {
            $os = 'Android';
        } elseif (str_contains($lower, 'iphone') || str_contains($lower, 'ipad')) {
            $os = 'iOS';
        } elseif (str_contains($lower, 'mac')) {
            $os = 'macOS';
        } elseif (str_contains($lower, 'linux')) {
            $os = 'Linux';
        }

        $browser = 'Bilinmeyen';
        if (str_contains($lower, 'edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($lower, 'chrome/')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($lower, 'firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($lower, 'safari/') && ! str_contains($lower, 'chrome/')) {
            $browser = 'Safari';
        }

        return [
            'device_type' => $deviceType,
            'os' => $os,
            'browser' => $browser,
        ];
    }

    private function guessLocation(?string $ip): string
    {
        if (! $ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Yerel Ağ';
        }

        return 'Türkiye';
    }
}
