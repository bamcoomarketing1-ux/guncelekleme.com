<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->foreignId('sponsor_id')->nullable()->after('id')->constrained('sponsors')->nullOnDelete();
            $table->decimal('amount', 14, 2)->default(0)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_id');
            $table->dropColumn('amount');
        });
    }
};
