<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_returns_json(): void
    {
        $this->getJson('/api/settings')->assertOk();
    }

    public function test_register_and_login_flow(): void
    {
        $this->postJson('/api/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ])->assertCreated();

        $this->postJson('/api/login', [
            'login' => 'testuser',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_mines_daily_limit_enforced(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        config(['platform.limits.mines_daily_plays' => 1]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/mines/start', ['bet' => 10, 'mines' => 5])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/games/mines/start', ['bet' => 10, 'mines' => 5])
            ->assertStatus(422);
    }
}
