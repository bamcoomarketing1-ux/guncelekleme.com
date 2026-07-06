<?php

use App\Models\SpecialOdd;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SpecialOdd::query()
            ->with(['league', 'homeTeam', 'awayTeam'])
            ->orderBy('id')
            ->each(function (SpecialOdd $odd) {
                $meta = $odd->syncTeamSnapshots(is_array($odd->meta) ? $odd->meta : []);
                if ($meta !== ($odd->meta ?? [])) {
                    $odd->update(['meta' => $meta]);
                }
            });
    }

    public function down(): void
    {
        // no-op
    }
};
