<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function send(User $user, string $title, ?string $body = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
        ]);
    }

    public function broadcast(string $title, ?string $body = null): int
    {
        $count = 0;
        User::where('is_active', true)->select('id')->chunkById(200, function ($users) use ($title, $body, &$count) {
            foreach ($users as $user) {
                $this->send($user, $title, $body);
                $count++;
            }
        });

        return $count;
    }
}
