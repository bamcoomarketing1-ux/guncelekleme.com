<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use Illuminate\Database\Eloquent\Model;

class SponsorCategory extends Model
{
    use MapsApiFields;

    protected $fillable = ['name', 'sort_order'];
}
