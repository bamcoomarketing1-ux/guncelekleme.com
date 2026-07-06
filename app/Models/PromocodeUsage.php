<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromocodeUsage extends Model
{
    protected $fillable = ['user_id', 'promocode_id'];

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }
}
