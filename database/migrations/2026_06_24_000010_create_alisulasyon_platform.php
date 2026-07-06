<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alisulasyon platform — tam MySQL şeması.
 * Sunucu kurulumu: php artisan migrate:fresh --seed && php artisan platform:install
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->decimal('balance', 14, 2)->default(0)->after('password');
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->boolean('is_moderator')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('avatar')->nullable();
            $table->string('wallet_trc20')->nullable();
            $table->string('wallet_erc20')->nullable();
            $table->string('wallet_iban')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->timestamp('telegram_verified_at')->nullable();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('Sistem Yöneticisi');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->string('position')->default('homepage');
            $table->string('size')->default('full');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sponsor_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('sponsor_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_carousel')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('social_media', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->nullable();
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->string('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_homepage')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('trial_bonuses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('reward_amount', 14, 2)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('special_odds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('odd_value', 8, 2)->nullable();
            $table->unsignedInteger('home_score')->nullable();
            $table->unsignedInteger('away_score')->nullable();
            $table->string('prediction')->nullable();
            $table->decimal('odds', 8, 2)->nullable();
            $table->decimal('bet_amount', 14, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('match_time')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('total_tickets')->default(0);
            $table->decimal('ticket_price', 14, 2)->default(0);
            $table->timestamp('event_date')->nullable();
            $table->boolean('show_on_homepage')->default(false);
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('ticket_price', 14, 2)->default(0);
            $table->unsignedInteger('max_tickets_per_user')->default(10);
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drawn_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('market_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->json('required_wallets')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('market_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('market_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('size')->nullable();
            $table->string('status')->default('pending');
            $table->json('winner')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->json('matches')->nullable();
            $table->json('participants')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wheel_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('balance');
            $table->decimal('value', 14, 2)->default(0);
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wheel_prize_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('reward', 14, 2)->default(0);
            $table->decimal('reward_amount', 14, 2)->default(0);
            $table->string('reward_type')->default('balance');
            $table->boolean('is_combo_spin')->default(false);
            $table->timestamps();
        });

        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('gif');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->string('link_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('music_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender')->default('user');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game');
            $table->decimal('bet', 14, 2)->default(0);
            $table->decimal('payout', 14, 2)->default(0);
            $table->string('status')->default('active');
            $table->json('state')->nullable();
            $table->timestamps();
        });

        Schema::create('promocode_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promocode_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('scratch_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('reward_amount', 14, 2)->default(0);
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('telegram_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bot_token')->nullable();
            $table->string('bot_username')->default('alisulasyonresmibot');
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('special_odd_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('special_odd_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('pending');
            $table->decimal('payout', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('raffle_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ticket_count')->default(1);
            $table->timestamps();
            $table->unique(['raffle_id', 'user_id']);
        });

        Schema::create('scratch_card_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scratch_card_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('reward_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('link_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('xp_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('action')->unique();
            $table->string('label');
            $table->unsignedInteger('xp_amount')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'xp_rewards', 'link_items', 'scratch_card_plays', 'raffle_participants', 'special_odd_bets',
            'ticket_participations', 'telegram_settings', 'scratch_cards', 'promocode_usages', 'game_sessions',
            'balance_transactions', 'support_messages', 'notifications', 'music_tracks', 'news_posts', 'popups',
            'wheel_spins', 'wheel_prizes', 'tournaments', 'market_orders', 'market_products', 'raffles',
            'ticket_requests', 'ticket_events', 'special_odds', 'teams', 'leagues', 'announcements', 'promocodes',
            'trial_bonuses', 'bonuses', 'social_media', 'sponsors', 'sponsor_categories', 'sliders', 'banners',
            'site_settings', 'admins',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'balance', 'xp', 'level', 'is_moderator', 'is_active',
                'avatar', 'wallet_trc20', 'wallet_erc20', 'wallet_iban',
                'telegram_chat_id', 'telegram_username', 'telegram_first_name', 'telegram_verified_at',
            ]);
        });
    }
};
