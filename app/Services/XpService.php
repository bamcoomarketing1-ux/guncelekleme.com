<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\XpReward;

class XpService
{
    public function isEnabled(): bool
    {
        $settings = SiteSetting::current();

        return (bool) ($settings['xp_system_enabled'] ?? config('platform.xp.enabled'));
    }

    public function add(User $user, string $action, ?int $amount = null): User
    {
        if (! $this->isEnabled()) {
            return $user;
        }

        $amount ??= XpReward::where('action', $action)->where('is_active', true)->value('xp_amount')
            ?? config("platform.xp.rewards.{$action}", 0);

        if ($amount <= 0) {
            return $user;
        }

        $user->xp = (int) $user->xp + $amount;
        $base = config('platform.xp.level_base', 1000);
        while ($user->xp >= $user->level * $base) {
            $user->level = (int) $user->level + 1;
        }
        $user->save();

        return $user->fresh();
    }

    public function progressFields(User $user): array
    {
        $base = (int) config('platform.xp.level_base', 1000);
        $level = max(1, (int) $user->level);
        $prevThreshold = ($level - 1) * $base;
        $nextLevelXp = $level * $base;
        $segmentSize = max(1, $nextLevelXp - $prevThreshold);
        $xpInSegment = max(0, (int) $user->xp - $prevThreshold);
        $percent = min(100, (int) round(($xpInSegment / $segmentSize) * 100));

        return [
            'xp_progress' => $percent,
            'next_level_xp' => $nextLevelXp,
        ];
    }

    public function userApiPayload(User $user): array
    {
        $data = $user->toArray();
        $data['balance'] = (float) $user->balance;

        return array_merge($data, $this->progressFields($user));
    }
}
