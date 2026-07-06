<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scratch_card_plays', function (Blueprint $table) {
            $table->boolean('is_scratched')->default(false)->after('reward_amount');
            $table->string('reward_type')->default('balance')->after('is_scratched');
            $table->json('payload')->nullable()->after('reward_type');
        });
    }

    public function down(): void
    {
        Schema::table('scratch_card_plays', function (Blueprint $table) {
            $table->dropColumn(['is_scratched', 'reward_type', 'payload']);
        });
    }
};
