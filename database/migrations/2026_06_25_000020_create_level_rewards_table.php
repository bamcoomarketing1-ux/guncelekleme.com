<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('level_rewards')) {
            return;
        }

        Schema::create('level_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('level')->unique();
            $table->string('reward_type')->default('balance');
            $table->decimal('reward_amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_rewards');
    }
};
