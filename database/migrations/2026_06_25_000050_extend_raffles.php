<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->string('reward_type')->default('points')->after('ticket_price');
            $table->string('total_prize')->nullable()->after('reward_type');
            $table->unsignedInteger('winner_count')->default(1)->after('total_prize');
            $table->text('rules')->nullable()->after('description');
            $table->string('status')->nullable()->after('is_active');
            $table->timestamp('starts_at')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->dropColumn(['reward_type', 'total_prize', 'winner_count', 'rules', 'status', 'starts_at']);
        });
    }
};
