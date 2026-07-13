<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecialOdd;
use App\Services\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    private function config(string $resource): array
    {
        $cfg = config("platform.admin.{$resource}");
        if (! $cfg) {
            abort(404, 'Resource not found');
        }
        return $cfg;
    }

    private function model(string $resource): string
    {
        return $this->config($resource)['model'];
    }

    public function index(string $resource): JsonResponse
    {
        $cfg = $this->config($resource);
        $class = $cfg['model'];
        $query = $class::query();
        $sample = new $class;
        if (in_array($resource, ['trial-bonuses', 'bonuses', 'ticket-events'], true)) {
            $query->with('sponsor');
        }
        if (in_array('sort_order', $sample->getFillable(), true)) {
            $query->orderBy('sort_order');
        }
        $items = $query->orderByDesc('id')->get()->map(fn (Model $m) => $m->toApiArray());

        if ($cfg['root_array'] ?? false) {
            return response()->json($items);
        }
        $key = $cfg['list_key'] ?? 'data';
        if ($key === 'data') {
            return response()->json(['status' => 'success', 'data' => $items]);
        }
        return response()->json([$key => $items]);
    }

    public function show(string $resource, int $id): JsonResponse
    {
        $cfg = $this->config($resource);
        $model = ($cfg['model'])::findOrFail($id);
        if (in_array($resource, ['trial-bonuses', 'bonuses', 'ticket-events'], true)) {
            $model->load('sponsor');
        }
        $singular = $cfg['singular'];
        $payload = $model->toApiArray();

        if ($resource === 'raffles') {
            $participants = $model->participants()
                ->with('user:id,username,name')
                ->get()
                ->map(function ($p) use ($model) {
                    $winnerIds = collect($model->winnersList())->pluck('username')->filter()->all();
                    $username = $p->user?->username ?? '—';

                    return [
                        'id' => $p->id,
                        'user_id' => $p->user_id,
                        'username' => $username,
                        'name' => $p->user?->name,
                        'ticket_count' => $p->ticket_count,
                        'joined_at' => $p->created_at?->format('d.m.Y H:i') ?? '',
                        'is_winner' => in_array($username, $winnerIds, true)
                            || (int) $model->winner_user_id === (int) $p->user_id,
                        'user' => [
                            'id' => $p->user_id,
                            'username' => $username,
                            'name' => $p->user?->name,
                        ],
                    ];
                });

            return response()->json([
                $singular => $payload,
                'data' => array_merge($payload, ['participants' => $participants->values()->all()]),
                'participants' => $participants->values()->all(),
            ]);
        }

        if ($resource === 'tournaments') {
            return response()->json($model->toDetailApiArray());
        }

        return response()->json([$singular => $payload, 'data' => $model->toApiArray()]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $cfg = $this->config($resource);
        $data = $this->mapInput($request->all(), $request, $resource);
        $model = ($cfg['model'])::create($data);
        if (in_array($resource, ['trial-bonuses', 'bonuses', 'ticket-events'], true)) {
            $model->load('sponsor');
        }
        if ($resource === 'special-odds' && $model instanceof SpecialOdd) {
            $this->persistSpecialOddSnapshots($model);
        }
        $api = $model->toApiArray();
        return response()->json([
            $cfg['singular'] => $api,
            'data' => $api,
            'status' => 'success',
            'message' => 'Kayıt eklendi.',
        ], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $cfg = $this->config($resource);
        $model = ($cfg['model'])::findOrFail($id);
        $model->update($this->mapInput($request->all(), $request, $resource));
        $model = $model->fresh();
        if (in_array($resource, ['trial-bonuses', 'bonuses', 'ticket-events'], true)) {
            $model->load('sponsor');
        }
        if ($resource === 'special-odds' && $model instanceof SpecialOdd) {
            $this->persistSpecialOddSnapshots($model);
        }
        return response()->json(['status' => 'success', 'data' => $model->toApiArray()]);
    }

    public function destroy(string $resource, int $id): JsonResponse
    {
        ($this->config($resource)['model'])::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Silindi.']);
    }

    public function reorder(Request $request, string $resource): JsonResponse
    {
        $class = $this->model($resource);
        $items = $request->input('orders', $request->input('order', $request->input('ids', [])));
        foreach ($items as $i => $item) {
            if (is_array($item) && isset($item['id'])) {
                $class::where('id', $item['id'])->update([
                    'sort_order' => (int) ($item['order'] ?? $i),
                ]);
            } else {
                $class::where('id', $item)->update(['sort_order' => $i + 1]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Sıralama güncellendi.']);
    }

    private function mapInput(array $data, ?Request $request = null, ?string $resource = null): array
    {
        if (isset($data['order']) && ! isset($data['sort_order'])) {
            $data['sort_order'] = $data['order'];
        }
        unset($data['order'], $data['_token'], $data['_method'], $data['id'], $data['created_at'], $data['updated_at']);

        if ($resource === 'raffles') {
            if (isset($data['end_date'])) {
                $data['ends_at'] = $data['end_date'];
                unset($data['end_date']);
            }
            if (isset($data['start_date'])) {
                $data['starts_at'] = $data['start_date'];
                unset($data['start_date']);
            }
            if (isset($data['image']) && is_string($data['image'])) {
                $data['image_url'] = $data['image'];
                unset($data['image']);
            }
        }

        if ($resource === 'trial-bonuses' || $resource === 'bonuses') {
            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                    || $data['is_active'] === '1'
                    || $data['is_active'] === 1;
            }
            if (isset($data['order']) && ! isset($data['sort_order'])) {
                $data['sort_order'] = (int) $data['order'];
            }
            if (isset($data['sponsor_id']) && $data['sponsor_id'] === '') {
                $data['sponsor_id'] = null;
            }
            if (isset($data['amount'])) {
                $data['amount'] = (float) $data['amount'];
            }
        }

        if ($resource === 'social-media') {
            if (isset($data['name']) && ! isset($data['title'])) {
                $data['title'] = $data['name'];
            }
            if (isset($data['type']) && ! isset($data['platform'])) {
                $data['platform'] = $data['type'];
            }
            if (isset($data['link']) && ! isset($data['url'])) {
                $data['url'] = $data['link'];
            }
            foreach (['is_active', 'show_on_homepage'] as $boolField) {
                if (array_key_exists($boolField, $data)) {
                    $data[$boolField] = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN)
                        || $data[$boolField] === '1'
                        || $data[$boolField] === 1;
                }
            }
            unset($data['name'], $data['type'], $data['link']);
        }

        if ($resource === 'news') {
            if (array_key_exists('is_published', $data)) {
                $data['is_active'] = filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN)
                    || $data['is_published'] === '1'
                    || $data['is_published'] === 1;
                unset($data['is_published']);
            }
            if (isset($data['order']) && ! isset($data['sort_order'])) {
                $data['sort_order'] = (int) $data['order'];
            }
            if (isset($data['title']) && empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['title']);
            }
        }

        if ($resource === 'ticket-events') {
            if (isset($data['sponsor_id']) && $data['sponsor_id'] === '') {
                $data['sponsor_id'] = null;
            }
            if (isset($data['end_date']) && ! isset($data['event_date'])) {
                $data['event_date'] = $data['end_date'];
                unset($data['end_date']);
            }
            if (! isset($data['status'])) {
                $data['status'] = 'active';
            }
            if (! isset($data['is_active'])) {
                $data['is_active'] = true;
            }
        }

        if ($resource === 'special-odds') {
            if (empty($data['title'])) {
                $homeTeamName = 'Ev Sahibi';
                $awayTeamName = 'Deplasman';
                
                if (!empty($data['home_team_id'])) {
                    $home = \App\Models\Team::find($data['home_team_id']);
                    if ($home) {
                        $homeTeamName = $home->name;
                    }
                }
                if (!empty($data['away_team_id'])) {
                    $away = \App\Models\Team::find($data['away_team_id']);
                    if ($away) {
                        $awayTeamName = $away->name;
                    }
                }
                $data['title'] = $homeTeamName . ' vs ' . $awayTeamName;
            }

            if (isset($data['odd_value']) && ! isset($data['odds'])) {
                $data['odds'] = $data['odd_value'];
            }
            if (isset($data['odds']) && ! isset($data['odd_value'])) {
                $data['odd_value'] = $data['odds'];
            }
            foreach (['league_id', 'home_team_id', 'away_team_id'] as $fk) {
                if (array_key_exists($fk, $data) && ($data[$fk] === '' || $data[$fk] === 'null')) {
                    $data[$fk] = null;
                }
            }
            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            if ($request) {
                foreach (['home_team', 'away_team', 'league'] as $snapshotKey) {
                    $snapshot = $request->input($snapshotKey);
                    if (is_array($snapshot) && ($snapshot['name'] ?? null)) {
                        $meta[$snapshotKey] = $snapshot;
                    }
                }
            }
            $data['meta'] = $meta;
        }

        if ($resource === 'market') {
            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                    || $data['is_active'] === '1'
                    || $data['is_active'] === 1;
            }
            if (array_key_exists('required_wallets', $data)) {
                $data['required_wallets'] = array_values(array_filter((array) $data['required_wallets']));
            } elseif ($request && in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
                $data['required_wallets'] = [];
            }
        }

        if ($resource === 'music') {
            if (isset($data['youtube_url']) && ! isset($data['url'])) {
                $data['url'] = $data['youtube_url'];
            }
            unset($data['youtube_url']);
        }

        if ($resource === 'tournaments') {
            if (isset($data['name']) && ! isset($data['title'])) {
                $data['title'] = $data['name'];
            }
            unset($data['name']);
            if (! isset($data['status'])) {
                $data['status'] = 'setup';
            }
            if (! isset($data['is_active'])) {
                $data['is_active'] = true;
            }
        }

        if ($request) {
            $upload = app(UploadService::class);
            $folder = $resource ?: 'uploads';
            foreach (['image', 'image_url', 'logo', 'logo_url', 'icon', 'icon_url', 'image_path'] as $field) {
                if ($request->hasFile($field)) {
                    $target = match ($field) {
                        'image' => $resource === 'market' ? 'image_path' : 'image_url',
                        'logo' => 'logo_url',
                        'icon' => 'icon_url',
                        default => $field,
                    };
                    $data[$target] = $upload->storeImage($request->file($field), $folder);
                }
            }
        }

        // Multipart alan adları; image_path DB yolu olarak kalmalı (market vb.).
        foreach (['image', 'logo', 'icon'] as $fileField) {
            unset($data[$fileField]);
        }

        if ($resource) {
            $fillable = (new ($this->config($resource)['model']))->getFillable();
            $data = array_intersect_key($data, array_flip($fillable));
        }

        return $data;
    }

    private function persistSpecialOddSnapshots(SpecialOdd $model): void
    {
        $model->load(['league', 'homeTeam', 'awayTeam']);
        $meta = $model->syncTeamSnapshots(is_array($model->meta) ? $model->meta : []);
        if ($meta !== ($model->meta ?? [])) {
            $model->update(['meta' => $meta]);
            $model->refresh();
        }
    }
}
