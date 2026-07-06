<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiCompatTest extends TestCase
{
    use RefreshDatabase;

    public function test_mines_start_reveal_and_cashout_match_frontend_shape(): void
    {
        $user = User::factory()->create(['balance' => 1000]);

        $start = $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/mines/start', ['bet_amount' => 10, 'mines_count' => 5])
            ->assertOk()
            ->json();

        $this->assertSame('success', $start['status']);
        $this->assertArrayHasKey('game', $start);
        $this->assertArrayHasKey('id', $start['game']);
        $this->assertSame(10.0, (float) $start['game']['bet_amount']);

        $reveal = $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/mines/reveal', ['cell_index' => 0])
            ->assertOk()
            ->json();

        $this->assertContains($reveal['status'], ['success', 'lost']);
        $this->assertArrayHasKey('game', $reveal);
        $this->assertArrayHasKey('opened_cells', $reveal['game']);

        if ($reveal['status'] === 'success') {
            $cashout = $this->actingAs($user, 'sanctum')
                ->postJson('/api/games/mines/cashout')
                ->assertOk()
                ->json();

            $this->assertSame('won', $cashout['status']);
            $this->assertArrayHasKey('win_amount', $cashout);
            $this->assertArrayHasKey('mines', $cashout);
        }
    }

    public function test_dice_play_returns_game_result_object(): void
    {
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/dice/play', [
                'bet_amount' => 10,
                'target' => 50,
                'direction' => 'over',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('success', $response['status']);
        $this->assertArrayHasKey('game', $response);
        $this->assertArrayHasKey('result', $response['game']);
        $this->assertArrayHasKey('status', $response['game']);
        $this->assertArrayHasKey('win_amount', $response['game']);
    }

    public function test_blackjack_play_returns_frontend_game_object(): void
    {
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/blackjack/play', ['bet_amount' => 10])
            ->assertOk()
            ->json();

        $this->assertSame('success', $response['status']);
        $this->assertArrayHasKey('game', $response);
        $this->assertArrayHasKey('id', $response['game']);
        $this->assertSame('in_progress', $response['game']['status']);
        $this->assertArrayHasKey('user_hand', $response['game']);
        $this->assertArrayHasKey('dealer_hand', $response['game']);
        $this->assertCount(2, $response['game']['user_hand']);
        $this->assertSame('hidden', $response['game']['dealer_hand'][1]);
        $this->assertIsString($response['game']['user_hand'][0]);
        $this->assertMatchesRegularExpression('/^[HDCS]-/', $response['game']['user_hand'][0]);
    }

    public function test_daily_stats_return_remaining_and_daily_limit(): void
    {
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/games/mines/daily-stats')
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('remaining', $response);
        $this->assertArrayHasKey('daily_limit', $response);
    }

    public function test_mines_active_returns_active_flag_and_game(): void
    {
        $user = User::factory()->create(['balance' => 1000]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/mines/start', ['bet_amount' => 10, 'mines_count' => 3])
            ->assertOk();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/games/mines/active')
            ->assertOk()
            ->json();

        $this->assertTrue($response['active']);
        $this->assertArrayHasKey('game', $response);
        $this->assertInstanceOf(GameSession::class, GameSession::find($response['game']['id']));
    }
}
