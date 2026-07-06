<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $fillable = [
        'visitor_key', 'user_id', 'path', 'ip', 'user_agent', 'referrer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
