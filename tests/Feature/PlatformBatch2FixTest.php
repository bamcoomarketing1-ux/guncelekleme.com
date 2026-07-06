<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\MusicTrack;
use App\Models\NewsPost;
use App\Models\Popup;
use App\Models\Raffle;
use App\Models\RaffleParticipant;
use App\Models\Tournament;
use App\Models\User;
use App\Models\GameSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformBatch2FixTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    private function user(): User
    {
        return User::factory()->create(['balance' => 1000]);
    }

    public function test_history_returns_formatted_rows(): void
    {
        $user = $this->user();
        GameSession::create([
            'user_id' => $user->id,
            'game' => 'mines',
            'bet' => 10,
            'payout' => 25,
            'status' => 'won',
            'state' => ['mine_cells' => [1, 2, 3], 'revealed' => [4, 5], 'multiplier' => 2.5],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/history?type=mines')
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('summary', $response);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertSame('mines', $response['data'][0]['type']);
        $this->assertSame('10', (string) (int) $response['data'][0]['bet_amount']);
        $this->assertNotEmpty($response['data'][0]['date_formatted']);
    }

    public function test_music_api_exposes_youtube_url(): void
    {
        MusicTrack::create([
            'title' => 'Test Song',
            'artist' => 'Artist',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_active' => true,
        ]);

        $item = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/music')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $item['youtube_url']);
        $this->assertSame('Artist', $item['artist']);
    }

    public function test_popup_store_returns_data_with_id(): void
    {
        $payload = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/popups', [
                'type' => 'normal',
                'title' => 'Test Popup',
                'is_active' => true,
            ])
            ->assertCreated()
            ->json();

        $this->assertArrayHasKey('data', $payload);
        $this->assertNotNull($payload['data']['id']);
    }

    public function test_raffle_participants_include_nested_user(): void
    {
        $user = $this->user();
        $raffle = Raffle::create([
            'title' => 'Test Raffle',
            'ticket_price' => 100,
            'is_active' => true,
            'status' => 'active',
        ]);
        RaffleParticipant::create([
            'raffle_id' => $raffle->id,
            'user_id' => $user->id,
            'ticket_count' => 1,
        ]);

        $participant = $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/admin/raffles/{$raffle->id}")
            ->assertOk()
            ->json('data.participants.0');

        $this->assertSame($user->username, $participant['user']['username']);
    }

    public function test_tournament_create_returns_tournament_object(): void
    {
        $payload = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/tournaments', ['name' => 'Cup', 'size' => 8])
            ->assertCreated()
            ->json();

        $this->assertSame('Cup', $payload['tournament']['name']);
        $this->assertDatabaseHas('tournaments', ['title' => 'Cup']);
    }

    public function test_news_show_by_slug(): void
    {
        NewsPost::create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => 'Body',
            'is_active' => true,
        ]);

        $this->getJson('/api/news/hello-world')
            ->assertOk()
            ->assertJsonPath('slug', 'hello-world');
    }

    public function test_popup_toggle_route(): void
    {
        $popup = Popup::create(['type' => 'normal', 'title' => 'P', 'is_active' => true]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/admin/popups/{$popup->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
