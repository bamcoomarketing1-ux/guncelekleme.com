<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use MapsApiFields;

    protected $fillable = ['user_id', 'sender', 'message'];

    /**
     * @return array{id: int, role: string, message: string, created_at: string|null}
     */
    public function toChatApiArray(): array
    {
        $sender = $this->sender ?? 'user';
        $role = match ($sender) {
            'user', 'guest' => 'user',
            'admin' => 'admin',
            default => 'bot',
        };

        return [
            'id' => $this->id,
            'role' => $role,
            'message' => $this->message,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
