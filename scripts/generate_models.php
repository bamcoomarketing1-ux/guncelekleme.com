<?php

$castsMap = [
    'Banner' => ['is_active' => 'boolean'],
    'Slider' => ['is_active' => 'boolean'],
    'Sponsor' => ['is_carousel' => 'boolean', 'is_active' => 'boolean'],
    'SocialMedia' => ['is_active' => 'boolean'],
    'Bonus' => ['is_featured' => 'boolean', 'is_active' => 'boolean'],
    'TrialBonus' => ['is_active' => 'boolean'],
    'Promocode' => ['reward_amount' => 'decimal:2', 'is_active' => 'boolean', 'expired_at' => 'datetime'],
    'Announcement' => ['is_active' => 'boolean'],
    'SpecialOdd' => ['odd_value' => 'decimal:2', 'is_active' => 'boolean'],
    'TicketEvent' => ['is_active' => 'boolean', 'event_date' => 'datetime'],
    'TicketRequest' => ['payload' => 'array'],
    'League' => ['is_active' => 'boolean'],
    'Raffle' => ['ticket_price' => 'decimal:2', 'is_active' => 'boolean', 'ends_at' => 'datetime'],
    'MarketProduct' => ['price' => 'decimal:2', 'required_wallets' => 'array', 'is_active' => 'boolean'],
    'MarketOrder' => ['payload' => 'array'],
    'Tournament' => ['matches' => 'array', 'participants' => 'array', 'is_active' => 'boolean'],
    'WheelPrize' => ['value' => 'decimal:2', 'is_active' => 'boolean'],
    'WheelSpin' => ['reward' => 'decimal:2'],
    'Popup' => ['is_active' => 'boolean'],
    'NewsPost' => ['is_active' => 'boolean'],
    'MusicTrack' => ['is_active' => 'boolean'],
    'Notification' => ['is_read' => 'boolean'],
    'BalanceTransaction' => ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'],
    'GameSession' => ['bet' => 'decimal:2', 'payout' => 'decimal:2', 'state' => 'array'],
];

$models = [
    'Banner' => ['title', 'image_url', 'link', 'position', 'size', 'is_active', 'sort_order'],
    'Slider' => ['title', 'image_url', 'link', 'is_active', 'sort_order'],
    'SponsorCategory' => ['name', 'sort_order'],
    'Sponsor' => ['category_id', 'name', 'description', 'logo_url', 'link', 'is_carousel', 'is_active', 'sort_order'],
    'SocialMedia' => ['platform', 'title', 'url', 'icon_url', 'is_active', 'sort_order'],
    'Bonus' => ['title', 'description', 'image_url', 'link', 'is_featured', 'is_active', 'sort_order'],
    'TrialBonus' => ['title', 'description', 'image_url', 'link', 'is_active', 'sort_order'],
    'Promocode' => ['code', 'reward_amount', 'usage_limit', 'used_count', 'expired_at', 'is_active'],
    'Announcement' => ['title', 'content', 'image_url', 'is_active', 'sort_order'],
    'SpecialOdd' => ['title', 'description', 'odd_value', 'is_active'],
    'TicketEvent' => ['title', 'description', 'image_url', 'total_tickets', 'event_date', 'is_active'],
    'TicketRequest' => ['user_id', 'ticket_event_id', 'status', 'payload'],
    'League' => ['name', 'logo_url', 'is_active'],
    'Team' => ['league_id', 'name', 'logo_url'],
    'Raffle' => ['title', 'description', 'image_url', 'ticket_price', 'ends_at', 'is_active'],
    'MarketProduct' => ['title', 'description', 'price', 'image_path', 'required_wallets', 'is_active', 'sort_order'],
    'MarketOrder' => ['user_id', 'market_product_id', 'status', 'payload'],
    'Tournament' => ['title', 'description', 'image_url', 'matches', 'participants', 'is_active'],
    'WheelPrize' => ['name', 'type', 'value', 'weight', 'is_active'],
    'WheelSpin' => ['user_id', 'wheel_prize_id', 'reward'],
    'Popup' => ['type', 'title', 'image_url', 'link', 'link_text', 'is_active', 'sort_order'],
    'NewsPost' => ['title', 'content', 'image_url', 'is_active'],
    'MusicTrack' => ['title', 'url', 'is_active'],
    'Notification' => ['user_id', 'title', 'body', 'is_read'],
    'SupportMessage' => ['user_id', 'sender', 'message'],
    'BalanceTransaction' => ['user_id', 'type', 'amount', 'balance_after', 'reference'],
    'GameSession' => ['user_id', 'game', 'bet', 'payout', 'status', 'state'],
    'PromocodeUsage' => ['user_id', 'promocode_id'],
];

$dir = dirname(__DIR__).'/app/Models';

foreach ($models as $name => $fields) {
    $trait = $name === 'PromocodeUsage' ? '' : "\n    use MapsApiFields;\n";
    $useTrait = $name === 'PromocodeUsage' ? '' : "use App\\Models\\Concerns\\MapsApiFields;\n";
    $table = $name === 'SocialMedia' ? "\n    protected \$table = 'social_media';\n" : '';
    $fill = "['".implode("', '", $fields)."']";
    $castsBlock = '';
    if (! empty($castsMap[$name])) {
        $lines = [];
        foreach ($castsMap[$name] as $k => $v) {
            $lines[] = "            '{$k}' => '{$v}',";
        }
        $castsBlock = "\n    protected \$casts = [\n".implode("\n", $lines)."\n    ];\n";
    }
    $content = <<<PHP
<?php

namespace App\Models;

{$useTrait}use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{{$trait}{$table}
    protected \$fillable = {$fill};{$castsBlock}
}

PHP;
    file_put_contents("{$dir}/{$name}.php", $content);
}

echo 'Models: '.count($models).PHP_EOL;
