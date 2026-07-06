<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiPayload extends Model
{
    protected $fillable = ['method', 'path', 'body'];

    protected function casts(): array
    {
        return ['body' => 'array'];
    }
}
