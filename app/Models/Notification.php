<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use MapsApiFields {
        toApiArray as mapsToApiArray;
    }

    protected $fillable = ['user_id', 'title', 'body', 'type', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        $row = $this->mapsToApiArray();
        $type = $this->type ?: 'info';
        if (! in_array($type, ['info', 'success', 'warning', 'danger'], true)) {
            $type = 'info';
        }

        $row['announcement'] = [
            'type' => $type,
            'title' => $this->title ?? '',
            'content' => $this->body ?? '',
        ];

        return $row;
    }
}
