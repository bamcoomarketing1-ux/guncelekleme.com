<?php

namespace App\Models;

use App\Models\Concerns\MapsApiFields;
use App\Services\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialOdd extends Model
{
    use MapsApiFields {
        toApiArray as protected baseApiArray;
    }

    protected $fillable = [
        'title', 'description', 'odd_value', 'is_active', 'league_id', 'home_team_id', 'away_team_id',
        'home_score', 'away_score', 'prediction', 'odds', 'bet_amount', 'status', 'match_time', 'meta',
    ];

    protected $casts = [
        'odd_value' => 'decimal:2',
        'odds' => 'decimal:2',
        'bet_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'match_time' => 'datetime',
        'meta' => 'array',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function bets()
    {
        return $this->hasMany(SpecialOddBet::class);
    }

    public function toApiArray(): array
    {
        $row = $this->baseApiArray();
        $meta = is_array($this->meta) ? $this->meta : [];

        $row['odds'] = $this->odds ?? $this->odd_value;
        $row['odd_value'] = $this->odd_value ?? $this->odds;
        $row['league'] = $this->leaguePayload($meta);
        $row['home_team'] = $this->teamPayload($this->homeTeam, $meta['home_team'] ?? null);
        $row['away_team'] = $this->teamPayload($this->awayTeam, $meta['away_team'] ?? null);
        $row['bets_count'] = $this->relationLoaded('bets_count')
            ? $this->bets_count
            : $this->bets()->count();
        $row['status'] = $this->resolvePublicStatus();
        $row['is_participated'] = (bool) ($row['is_participated'] ?? false);
        $row['match_time_formatted'] = $this->match_time?->format('d/m H:i') ?? '';
        $row['prediction'] = $this->prediction ?? $this->title ?? '';

        return $row;
    }

    public function toApiArrayForUser(?User $user = null): array
    {
        $row = $this->toApiArray();
        if ($user) {
            $row['is_participated'] = SpecialOddBet::where('user_id', $user->id)
                ->where('special_odd_id', $this->id)
                ->exists();
        }

        return $row;
    }

    /** @param  array<string, mixed>  $meta */
    public function syncTeamSnapshots(array $meta = []): array
    {
        $meta = $meta ?: (is_array($this->meta) ? $this->meta : []);

        if ($this->home_team_id) {
            $home = $this->homeTeam ?: Team::find($this->home_team_id);
            if ($home) {
                $meta['home_team'] = $home->toApiArray();
            }
        }

        if ($this->away_team_id) {
            $away = $this->awayTeam ?: Team::find($this->away_team_id);
            if ($away) {
                $meta['away_team'] = $away->toApiArray();
            }
        }

        if ($this->league_id) {
            $league = $this->league ?: League::find($this->league_id);
            if ($league) {
                $meta['league'] = $league->toApiArray();
            }
        }

        return $meta;
    }

    private function resolvePublicStatus(): string
    {
        if ($this->status) {
            return $this->status;
        }

        return $this->is_active ? 'active' : 'ended';
    }

    /** @param  array<string, mixed>|null  $metaFallback */
    private function teamPayload(?Team $team, ?array $metaFallback = null): array
    {
        if ($team) {
            $payload = $team->toApiArray();
            $payload['logo_url'] = $this->normalizeLogoUrl($payload['logo_url'] ?? null);

            return $payload;
        }

        if (is_array($metaFallback) && ($metaFallback['name'] ?? null)) {
            return [
                'id' => $metaFallback['id'] ?? null,
                'name' => $metaFallback['name'],
                'logo_url' => $this->normalizeLogoUrl($metaFallback['logo_url'] ?? null),
                'is_active' => (bool) ($metaFallback['is_active'] ?? true),
            ];
        }

        return [
            'id' => null,
            'name' => 'Belirlenmedi',
            'logo_url' => '/placeholder.png',
            'is_active' => false,
        ];
    }

    /** @param  array<string, mixed>  $meta */
    private function leaguePayload(array $meta): ?array
    {
        if ($this->league) {
            return $this->league->toApiArray();
        }

        if (isset($meta['league']) && is_array($meta['league']) && ($meta['league']['name'] ?? null)) {
            return $meta['league'];
        }

        return null;
    }

    private function normalizeLogoUrl(?string $url): ?string
    {
        if (! $url) {
            return '/placeholder.png';
        }

        $url = trim($url);
        if ($url === '') {
            return '/placeholder.png';
        }

        if (preg_match('#^https?://#i', $url)) {
            $url = preg_replace('#https?://[^/]+/storage/#', '/storage/', $url) ?? $url;
        } elseif (str_starts_with($url, 'storage/')) {
            $url = '/'.$url;
        } elseif (! str_starts_with($url, '/')) {
            $url = '/storage/'.ltrim($url, '/');
        }

        if (str_starts_with($url, '/storage/') && ! UploadService::resolvePublicPath($url)) {
            return '/placeholder.png';
        }

        return $url;
    }
}
