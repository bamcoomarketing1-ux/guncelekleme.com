<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_code', 10)->nullable()->after('email_verified_at');
            $table->timestamp('verification_expires_at')->nullable()->after('verification_code');
            $table->string('telegram_verification_code', 32)->nullable()->after('telegram_verified_at');
            $table->timestamp('telegram_verification_expires_at')->nullable()->after('telegram_verification_code');
        });

        Schema::create('user_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->timestamps();
            $table->unique(['user_id', 'sponsor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sponsors');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_expires_at',
                'telegram_verification_code',
                'telegram_verification_expires_at',
            ]);
        });
    }
};
