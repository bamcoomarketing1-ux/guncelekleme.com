<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_events', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_events', 'sponsor_id')) {
                $table->foreignId('sponsor_id')->nullable()->after('id')->constrained('sponsors')->nullOnDelete();
            }
        });

        Schema::table('news_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('news_posts', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (! Schema::hasColumn('news_posts', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('category');
            }
            if (! Schema::hasColumn('news_posts', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_events', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_events', 'sponsor_id')) {
                $table->dropConstrainedForeignId('sponsor_id');
            }
        });

        Schema::table('news_posts', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['category', 'excerpt', 'sort_order'],
                fn (string $col) => Schema::hasColumn('news_posts', $col)
            ));
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};
