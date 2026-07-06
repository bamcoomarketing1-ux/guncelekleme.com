<?php

return [
    'limits' => [
        'mines_daily_plays' => (int) env('MINES_DAILY_PLAYS', 100),
        'dice_daily_plays' => (int) env('DICE_DAILY_PLAYS', 100),
        'scratch_daily_plays' => (int) env('SCRATCH_DAILY_PLAYS', 5),
        'wheel_daily_spins' => 1,
    ],

    'xp' => [
        'enabled' => filter_var(env('XP_SYSTEM_ENABLED', false), FILTER_VALIDATE_BOOL),
        'level_base' => 1000,
        'rewards' => [
            'mines_win' => 10,
            'dice_win' => 5,
            'blackjack_win' => 15,
            'wheel_spin' => 20,
            'scratch_card' => 8,
            'market_order' => 25,
            'promo' => 5,
            'raffle_join' => 10,
            'special_odd_bet' => 12,
        ],
    ],

    'admin_permissions' => [
        'Sistem Yöneticisi' => ['*'],
        'Moderatör' => ['dashboard', 'users.read', 'support', 'market-orders'],
        'İçerik Yöneticisi' => ['dashboard', 'banners', 'sliders', 'sponsors', 'bonuses', 'news', 'music', 'popups'],
    ],

    'admin' => [
        'banners' => ['model' => \App\Models\Banner::class, 'list_key' => 'banners', 'singular' => 'banner'],
        'sliders' => ['model' => \App\Models\Slider::class, 'list_key' => 'sliders', 'singular' => 'slider'],
        'sponsors' => ['model' => \App\Models\Sponsor::class, 'list_key' => 'sponsors', 'singular' => 'sponsor'],
        'sponsor-categories' => ['model' => \App\Models\SponsorCategory::class, 'list_key' => 'categories', 'singular' => 'category'],
        'social-media' => ['model' => \App\Models\SocialMedia::class, 'list_key' => 'data', 'singular' => 'social_media'],
        'bonuses' => ['model' => \App\Models\Bonus::class, 'list_key' => 'data', 'singular' => 'bonus'],
        'trial-bonuses' => ['model' => \App\Models\TrialBonus::class, 'list_key' => 'data', 'singular' => 'trial_bonus'],
        'promocodes' => ['model' => \App\Models\Promocode::class, 'list_key' => 'data', 'singular' => 'promocode'],
        'announcements' => ['model' => \App\Models\Announcement::class, 'list_key' => 'data', 'singular' => 'announcement'],
        'special-odds' => ['model' => \App\Models\SpecialOdd::class, 'list_key' => 'data', 'singular' => 'special_odd'],
        'ticket-events' => ['model' => \App\Models\TicketEvent::class, 'list_key' => 'data', 'singular' => 'ticket_event'],
        'ticket-requests' => ['model' => \App\Models\TicketRequest::class, 'list_key' => 'data', 'singular' => 'ticket_request'],
        'leagues' => ['model' => \App\Models\League::class, 'list_key' => 'data', 'singular' => 'league'],
        'teams' => ['model' => \App\Models\Team::class, 'list_key' => 'data', 'singular' => 'team'],
        'raffles' => ['model' => \App\Models\Raffle::class, 'list_key' => 'data', 'singular' => 'raffle'],
        'market' => ['model' => \App\Models\MarketProduct::class, 'list_key' => null, 'singular' => 'product', 'root_array' => true],
        'market-orders' => ['model' => \App\Models\MarketOrder::class, 'list_key' => 'data', 'singular' => 'order'],
        'tournaments' => ['model' => \App\Models\Tournament::class, 'list_key' => null, 'singular' => 'tournament', 'root_array' => true],
        'wheel' => ['model' => \App\Models\WheelPrize::class, 'list_key' => 'data', 'singular' => 'prize'],
        'popups' => ['model' => \App\Models\Popup::class, 'list_key' => 'data', 'singular' => 'popup'],
        'news' => ['model' => \App\Models\NewsPost::class, 'list_key' => 'data', 'singular' => 'news'],
        'music' => ['model' => \App\Models\MusicTrack::class, 'list_key' => 'data', 'singular' => 'music'],
        'link-items' => ['model' => \App\Models\LinkItem::class, 'list_key' => 'data', 'singular' => 'link_item'],
        'xp-rewards' => ['model' => \App\Models\XpReward::class, 'list_key' => 'data', 'singular' => 'xp_reward'],
    ],
];
