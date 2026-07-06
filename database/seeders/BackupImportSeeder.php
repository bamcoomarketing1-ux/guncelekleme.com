<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Bonus;
use App\Models\League;
use App\Models\MarketOrder;
use App\Models\MarketProduct;
use App\Models\MusicTrack;
use App\Models\NewsPost;
use App\Models\Popup;
use App\Models\Promocode;
use App\Models\Raffle;
use App\Models\Sponsor;
use App\Models\SponsorCategory;
use App\Models\Slider;
use App\Models\SocialMedia;
use App\Models\SpecialOdd;
use App\Models\Team;
use App\Models\TicketEvent;
use App\Models\TicketRequest;
use App\Models\Tournament;
use App\Models\TrialBonus;
use App\Models\ScratchCard;
use App\Models\TelegramSetting;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\XpReward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BackupImportSeeder extends Seeder
{
    private string $backup;

    public function run(): void
    {
        $this->backup = realpath(base_path('../alisulasyon-clone/data/backup'))
            ?: realpath(base_path('../alisulasyon51/backup'));

        if (! $this->backup) {
            $this->command?->error('Backup klasörü bulunamadı.');
            return;
        }

        $this->importSettings();
        $this->importUsers();
        $this->importAdmins();
        $this->importCmsTables();
        $this->importPublicContent();
        $this->importTournamentDetails();
        $this->importExtensions();
        $this->command?->info('Yedek import tamamlandı.');
    }

    private function unwrap(array $raw): mixed
    {
        return $raw['body'] ?? $raw;
    }

    private function readJson(string $rel): ?array
    {
        $p = $this->backup.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (! is_file($p)) {
            return null;
        }
        return json_decode(file_get_contents($p), true);
    }

    private function importSettings(): void
    {
        $raw = $this->readJson('api/public/settings.json');
        if (! $raw) {
            return;
        }
        $data = $this->unwrap($raw);
        SiteSetting::query()->updateOrCreate(['id' => 1], ['data' => $data['data'] ?? $data]);
    }

    private function importUsers(): void
    {
        $raw = $this->readJson('api/admin/users_all.json');
        if (! $raw || empty($raw['data'])) {
            return;
        }

        $defaultHash = Hash::make('Test123.');
        $rows = [];
        foreach ($raw['data'] as $u) {
            $rows[] = [
                'id' => $u['id'],
                'name' => $u['name'] ?? 'User',
                'username' => $u['username'] ?? ('user'.$u['id']),
                'email' => $u['email'] ?? ('u'.$u['id'].'@local.test'),
                'phone' => $u['phone'] ?? null,
                'password' => $defaultHash,
                'email_verified_at' => $u['email_verified_at'] ?? now(),
                'balance' => $u['balance'] ?? 0,
                'xp' => $u['xp'] ?? 0,
                'level' => $u['level'] ?? 1,
                'is_moderator' => (bool) ($u['is_moderator'] ?? false),
                'is_active' => (bool) ($u['is_active'] ?? true),
                'avatar' => $u['avatar'] ?? null,
                'wallet_trc20' => $u['wallet_trc20'] ?? null,
                'wallet_erc20' => $u['wallet_erc20'] ?? null,
                'wallet_iban' => $u['wallet_iban'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('users')->upsert($chunk, ['id'], array_keys($chunk[0]));
        }
    }

    private function importAdmins(): void
    {
        $raw = $this->readJson('api/admin/admin_admins.json');
        if (! $raw) {
            return;
        }
        $body = $this->unwrap($raw);
        foreach ($body['data'] ?? [] as $a) {
            Admin::updateOrCreate(
                ['email' => $a['email']],
                [
                    'username' => $a['username'],
                    'role' => $a['role'] ?? 'Sistem Yöneticisi',
                    'password' => Hash::make(
                        $a['email'] === 'test@gmail.com' ? 'testtest' : 'adminadminadminadminadmin'
                    ),
                ]
            );
        }
    }

    private function normalizeStorage(?string $url): ?string
    {
        if (! $url) {
            return $url;
        }
        return preg_replace('#https?://[^/]+/storage/#', '/storage/', $url) ?? $url;
    }

    private function importList(string $file, string $key, string $modelClass, array $map, bool $rootArray = false): void
    {
        $raw = $this->readJson($file);
        if (! $raw) {
            return;
        }
        $body = $this->unwrap($raw);
        $items = $rootArray && is_array($body) && array_is_list($body)
            ? $body
            : ($body[$key] ?? (is_array($body) && array_is_list($body) ? $body : []));
        if (! is_array($items)) {
            return;
        }
        foreach ($items as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }
            $data = ['id' => $row['id']];
            foreach ($map as $src => $dst) {
                $val = $row[$src] ?? null;
                if (str_contains($dst, 'url') || str_contains($dst, 'path')) {
                    $val = $this->normalizeStorage($val);
                }
                $data[$dst] = $val;
            }
            if ($modelClass === Tournament::class) {
                if (empty($data['title']) && ! empty($row['name'])) {
                    $data['title'] = $row['name'];
                }
                if (empty($data['participants'])) {
                    $data['participants'] = $row;
                }
                $data['is_active'] = $row['is_active'] ?? true;
            }
            if ($modelClass === SpecialOdd::class && empty($data['title'])) {
                $data['title'] = $row['prediction'] ?? $row['title'] ?? 'Özel Oran #'.($row['id'] ?? '');
            }
            if ($modelClass === SpecialOdd::class) {
                if (! empty($data['league_id']) && ! League::where('id', $data['league_id'])->exists()) {
                    $data['league_id'] = null;
                }
                if (! empty($data['home_team_id']) && ! Team::where('id', $data['home_team_id'])->exists()) {
                    $data['home_team_id'] = null;
                }
                if (! empty($data['away_team_id']) && ! Team::where('id', $data['away_team_id'])->exists()) {
                    $data['away_team_id'] = null;
                }
            }
            if ($modelClass === Team::class && ! empty($data['league_id']) && ! League::where('id', $data['league_id'])->exists()) {
                $data['league_id'] = null;
            }
            $modelClass::updateOrCreate(['id' => $data['id']], $data);
        }
    }

    private function importCmsTables(): void
    {
        $this->importList('api/admin/admin_banners.json', 'banners', Banner::class, [
            'title' => 'title', 'image_url' => 'image_url', 'link' => 'link',
            'position' => 'position', 'size' => 'size', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_sliders.json', 'sliders', Slider::class, [
            'title' => 'title', 'image_url' => 'image_url', 'link' => 'link', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_sponsor-categories.json', 'categories', SponsorCategory::class, [
            'name' => 'name', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_sponsors.json', 'sponsors', Sponsor::class, [
            'category_id' => 'category_id', 'name' => 'name', 'description' => 'description',
            'logo_url' => 'logo_url', 'link' => 'link', 'is_carousel' => 'is_carousel', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_social-media.json', 'data', SocialMedia::class, [
            'name' => 'title', 'type' => 'platform', 'url' => 'url', 'icon_url' => 'icon_url',
            'is_active' => 'is_active', 'order' => 'sort_order', 'show_on_homepage' => 'show_on_homepage',
        ]);
        $this->importList('api/admin/admin_bonuses.json', 'bonuses', Bonus::class, [
            'title' => 'title', 'description' => 'description', 'image_url' => 'image_url', 'link' => 'link',
            'sponsor_id' => 'sponsor_id', 'amount' => 'amount',
            'is_featured' => 'is_featured', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_trial-bonuses.json', 'trial_bonuses', TrialBonus::class, [
            'title' => 'title', 'description' => 'description', 'image_url' => 'image_url', 'link' => 'link',
            'sponsor_id' => 'sponsor_id', 'amount' => 'amount', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_promocodes.json', 'promocodes', Promocode::class, [
            'code' => 'code', 'reward_amount' => 'reward_amount', 'usage_limit' => 'usage_limit', 'used_count' => 'used_count', 'expired_at' => 'expired_at', 'is_active' => 'is_active',
        ]);
        $this->importList('api/admin/admin_announcements.json', 'announcements', Announcement::class, [
            'title' => 'title', 'content' => 'content', 'image_url' => 'image_url', 'is_active' => 'is_active', 'order' => 'sort_order',
        ]);
        $this->importList('api/admin/admin_leagues.json', 'leagues', League::class, [
            'name' => 'name', 'logo_url' => 'logo_url', 'country' => 'country', 'is_active' => 'is_active',
        ]);
        $this->importList('api/admin/admin_teams.json', 'teams', Team::class, [
            'league_id' => 'league_id', 'name' => 'name', 'logo_url' => 'logo_url', 'is_active' => 'is_active',
        ]);
        $this->importList('api/admin/admin_special-odds.json', 'data', SpecialOdd::class, [
            'title' => 'title', 'description' => 'description', 'odd_value' => 'odd_value',
            'league_id' => 'league_id', 'home_team_id' => 'home_team_id', 'away_team_id' => 'away_team_id',
            'home_score' => 'home_score', 'away_score' => 'away_score', 'prediction' => 'prediction',
            'odds' => 'odds', 'bet_amount' => 'bet_amount', 'status' => 'status', 'match_time' => 'match_time',
        ]);
        $this->importList('api/admin/admin_ticket-events.json', 'ticket_events', TicketEvent::class, [
            'title' => 'title', 'description' => 'description', 'image_url' => 'image_url', 'total_tickets' => 'total_tickets', 'event_date' => 'event_date', 'is_active' => 'is_active',
        ]);
        $this->importList('api/admin/admin_raffles.json', 'raffles', Raffle::class, [
            'title' => 'title', 'description' => 'description', 'image_url' => 'image_url', 'ticket_price' => 'ticket_price', 'ends_at' => 'ends_at', 'is_active' => 'is_active',
        ]);

        $marketRaw = $this->readJson('api/admin/admin_market.json');
        if ($marketRaw) {
            $items = $this->unwrap($marketRaw);
            if (is_array($items) && array_is_list($items)) {
                foreach ($items as $row) {
                    MarketProduct::updateOrCreate(['id' => $row['id']], [
                        'title' => $row['title'] ?? '',
                        'description' => $row['description'] ?? null,
                        'price' => $row['price'] ?? 0,
                        'image_path' => $this->normalizeStorage($row['image_path'] ?? null),
                        'required_wallets' => $row['required_wallets'] ?? null,
                        'is_active' => $row['is_active'] ?? true,
                        'sort_order' => $row['sort_order'] ?? 0,
                    ]);
                }
            }
        }

        $this->importList('api/admin/admin_tournaments.json', 'tournaments', Tournament::class, [
            'name' => 'title', 'description' => 'description', 'image_url' => 'image_url',
            'size' => 'size', 'status' => 'status', 'winner' => 'winner',
            'matches' => 'matches', 'participants' => 'participants',
        ], true);
        $wheelRaw = $this->readJson('api/admin/admin_wheel.json');
        if ($wheelRaw) {
            $body = $this->unwrap($wheelRaw);
            $items = $body['data'] ?? $body['prizes'] ?? [];
            foreach ($items as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                WheelPrize::updateOrCreate(['id' => $row['id']], [
                    'name' => $row['name'] ?? 'Prize',
                    'type' => $row['type'] ?? 'balance',
                    'value' => $row['value'] ?? 0,
                    'weight' => $row['weight'] ?? 1,
                    'is_active' => $row['is_active'] ?? true,
                ]);
            }
        }

        $ordersRaw = $this->readJson('api/admin/admin_market-orders.json');
        if ($ordersRaw) {
            $body = $this->unwrap($ordersRaw);
            foreach ($body['data'] ?? [] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                MarketOrder::updateOrCreate(['id' => $row['id']], [
                    'user_id' => $row['user_id'] ?? null,
                    'market_product_id' => $row['market_product_id'] ?? $row['product_id'] ?? null,
                    'status' => $row['status'] ?? 'pending',
                    'payload' => $row,
                ]);
            }
        }

        $ticketsRaw = $this->readJson('api/admin/admin_ticket-requests.json');
        if ($ticketsRaw) {
            $body = $this->unwrap($ticketsRaw);
            foreach ($body['data'] ?? $body['ticket_requests'] ?? [] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                TicketRequest::updateOrCreate(['id' => $row['id']], [
                    'user_id' => $row['user_id'] ?? null,
                    'ticket_event_id' => $row['ticket_event_id'] ?? null,
                    'status' => $row['status'] ?? 'pending',
                    'payload' => $row,
                ]);
            }
        }
    }

    private function importPublicContent(): void
    {
        $popupRaw = $this->readJson('api/public/popup.json');
        if ($popupRaw) {
            $body = $this->unwrap($popupRaw);
            foreach ($body['data'] ?? [] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                Popup::updateOrCreate(['id' => $row['id']], [
                    'type' => $row['type'] ?? 'gif',
                    'title' => $row['title'] ?? null,
                    'description' => $row['description'] ?? null,
                    'image_url' => $this->normalizeStorage($row['image_url'] ?? $row['image'] ?? null),
                    'link' => $row['link'] ?? null,
                    'link_text' => $row['link_text'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => $row['sort_order'] ?? 0,
                ]);
            }
        }

        foreach ([
            ['api/public/news.json', NewsPost::class, ['title' => 'title', 'content' => 'content', 'image_url' => 'image_url', 'is_active' => 'is_active']],
            ['api/public/music.json', MusicTrack::class, ['title' => 'title', 'url' => 'url', 'is_active' => 'is_active']],
        ] as [$file, $model, $map]) {
            $raw = $this->readJson($file);
            if (! $raw) {
                continue;
            }
            $body = $this->unwrap($raw);
            foreach ($body['data'] ?? [] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                $data = ['id' => $row['id']];
                foreach ($map as $src => $dst) {
                    $val = $row[$src] ?? null;
                    if (str_contains($dst, 'url')) {
                        $val = $this->normalizeStorage($val);
                    }
                    $data[$dst] = $val;
                }
                $model::updateOrCreate(['id' => $data['id']], $data);
            }
        }
    }

    private function importTournamentDetails(): void
    {
        $dir = $this->backup.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'tournaments';
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.DIRECTORY_SEPARATOR.'tournament_*.json') ?: [] as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (! is_array($data) || empty($data['tournament']['id'])) {
                continue;
            }
            $t = $data['tournament'];
            Tournament::updateOrCreate(['id' => $t['id']], [
                'title' => $t['name'] ?? 'Turnuva',
                'size' => $t['size'] ?? 8,
                'status' => $t['status'] ?? 'pending',
                'winner' => $t['winner'] ?? null,
                'participants' => $data['participants'] ?? null,
                'matches' => $data['matches'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    private function importExtensions(): void
    {
        TelegramSetting::firstOrCreate([], [
            'bot_username' => 'alisulasyonresmibot',
            'is_active' => true,
        ]);

        if (ScratchCard::count() === 0) {
            foreach ([
                ['title' => '100 Puan', 'reward_amount' => 100, 'weight' => 50],
                ['title' => '500 Puan', 'reward_amount' => 500, 'weight' => 30],
                ['title' => '1000 Puan', 'reward_amount' => 1000, 'weight' => 15],
                ['title' => '5000 Puan', 'reward_amount' => 5000, 'weight' => 5],
            ] as $card) {
                ScratchCard::create(array_merge($card, ['is_active' => true]));
            }
        }

        foreach (config('platform.xp.rewards', []) as $action => $amount) {
            XpReward::updateOrCreate(
                ['action' => $action],
                ['label' => ucfirst(str_replace('_', ' ', $action)), 'xp_amount' => $amount, 'is_active' => true]
            );
        }

        if (Announcement::count() === 0) {
            Announcement::create(['title' => 'Hoş geldiniz', 'content' => 'Platform aktif', 'is_active' => true]);
        }
        if (Bonus::where('id', 1)->doesntExist() && Bonus::count() > 0) {
            // use first bonus
        } elseif (Bonus::count() === 0) {
            Bonus::create(['title' => 'Test Bonus', 'description' => 'Test', 'is_active' => true, 'is_featured' => false]);
        }

        $historyRaw = $this->readJson('api/admin/admin_wheel_history.json');
        if ($historyRaw) {
            $body = $this->unwrap($historyRaw);
            foreach ($body['data'] ?? [] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                WheelSpin::updateOrCreate(['id' => $row['id']], [
                    'user_id' => $row['user_id'] ?? null,
                    'wheel_prize_id' => $row['wheel_item_id'] ?? $row['wheel_prize_id'] ?? null,
                    'reward' => $row['reward_amount'] ?? 0,
                    'reward_amount' => $row['reward_amount'] ?? 0,
                    'reward_type' => $row['reward_type'] ?? 'balance',
                    'is_combo_spin' => $row['is_combo_spin'] ?? false,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
            }
        }
        $this->command?->info('Wheel history (sayfa 1) import edildi. Tam: php artisan wheel:import-history --api=URL --token=TOKEN');
    }

    // ApiPayload catch-all kaldırıldı — stub import artık gerekmez.
}
