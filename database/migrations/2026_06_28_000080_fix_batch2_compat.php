<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('music_tracks', function (Blueprint $table) {
            if (! Schema::hasColumn('music_tracks', 'artist')) {
                $table->string('artist')->nullable()->after('title');
            }
            if (! Schema::hasColumn('music_tracks', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        Schema::table('news_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('news_posts', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }
        });

        if (Schema::hasColumn('news_posts', 'slug')) {
            foreach (\App\Models\NewsPost::whereNull('slug')->get() as $post) {
                $base = Str::slug($post->title) ?: 'haber-'.$post->id;
                $slug = $base;
                $i = 1;
                while (\App\Models\NewsPost::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $post->update(['slug' => $slug]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('music_tracks', function (Blueprint $table) {
            foreach (['artist', 'sort_order'] as $col) {
                if (Schema::hasColumn('music_tracks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('news_posts', function (Blueprint $table) {
            if (Schema::hasColumn('news_posts', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
