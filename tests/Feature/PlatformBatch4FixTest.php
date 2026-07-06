<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Sponsor;
use App\Models\TicketEvent;
use App\Models\TicketParticipation;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformBatch4FixTest extends TestCase
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

    public function test_admin_lists_ticket_event_requests_with_flat_shape(): void
    {
        $event = TicketEvent::create([
            'title' => 'Event',
            'description' => 'Desc',
            'total_tickets' => 10,
            'ticket_price' => 5,
            'event_date' => now()->addDay(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'tester',
            'email' => 'tester@example.com',
        ]);

        TicketRequest::create([
            'user_id' => $user->id,
            'ticket_event_id' => $event->id,
            'status' => 'pending',
            'payload' => [
                'investment_amount' => 500,
                'screenshot_url' => '/storage/ticket-requests/proof.jpg',
            ],
        ]);

        $row = $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/admin/ticket-events/{$event->id}/requests")
            ->assertOk()
            ->json('data.0');

        $this->assertSame(500, $row['investment_amount']);
        $this->assertSame('/storage/ticket-requests/proof.jpg', $row['screenshot_url']);
        $this->assertSame('tester', $row['user']['username']);
    }

    public function test_admin_approve_uses_ticket_count_from_request(): void
    {
        $event = TicketEvent::create([
            'title' => 'Event',
            'total_tickets' => 10,
            'ticket_price' => 5,
            'event_date' => now()->addDay(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $ticketRequest = TicketRequest::create([
            'user_id' => $user->id,
            'ticket_event_id' => $event->id,
            'status' => 'pending',
            'payload' => ['investment_amount' => 100, 'screenshot_url' => '/storage/x.jpg'],
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/admin/ticket-requests/{$ticketRequest->id}/approve", [
                'ticket_count' => 3,
            ])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $ticketRequest->refresh();
        $this->assertSame('approved', $ticketRequest->status);
        $this->assertSame(3, $ticketRequest->payload['approved_ticket_count']);
        $this->assertSame(3, TicketParticipation::where('ticket_event_id', $event->id)->count());
    }

    public function test_admin_lists_ticket_event_participations_grouped_by_user(): void
    {
        $event = TicketEvent::create([
            'title' => 'Summer Draw',
            'total_tickets' => 100,
            'ticket_price' => 10,
            'event_date' => now()->addWeek(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Player One',
            'username' => 'player1',
            'email' => 'player1@example.com',
        ]);

        foreach (['000001', '000002'] as $ticketNumber) {
            TicketParticipation::create([
                'user_id' => $user->id,
                'ticket_event_id' => $event->id,
                'status' => 'active',
                'payload' => ['ticket_number' => $ticketNumber, 'is_winner' => false],
            ]);
        }

        $row = $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/admin/ticket-events/{$event->id}/participations")
            ->assertOk()
            ->json('data.0');

        $this->assertSame($user->id, $row['user_id']);
        $this->assertSame(2, $row['ticket_count']);
        $this->assertSame('player1', $row['user']['username']);
        $this->assertFalse($row['has_winner']);
    }

    public function test_analytics_endpoints_track_visit_and_sponsor_click(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Sponsor A',
            'logo_url' => '/storage/logo.png',
            'link' => 'https://example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->postJson('/api/analytics/visit', ['path' => '/biletler'])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->postJson("/api/sponsors/{$sponsor->id}/click")
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $summary = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/statistics?period=7')
            ->assertOk()
            ->json('data.summary');

        $this->assertGreaterThanOrEqual(1, $summary['visitors_period']);
        $this->assertGreaterThanOrEqual(1, $summary['sponsor_clicks_period']);
        $this->assertArrayHasKey('sponsor_clicks_breakdown', $summary);
    }

    public function test_market_purchase_and_history_routes(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $product = \App\Models\MarketProduct::create([
            'title' => 'Demo',
            'description' => 'Test product',
            'price' => 10,
            'image_path' => '/storage/market/missing.png',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/market/{$product->id}/purchase")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['user' => ['balance']]);

        $history = $this->actingAs($user, 'sanctum')
            ->getJson('/api/market/history')
            ->assertOk()
            ->json();

        $this->assertCount(1, $history);
        $this->assertSame('Demo', $history[0]['product']['title']);
        $this->assertSame('/storage/market/missing.png', $history[0]['product']['image_path']);
    }

    public function test_remove_tickets_returns_deleted_ids(): void
    {
        $event = TicketEvent::create([
            'title' => 'Event',
            'total_tickets' => 10,
            'ticket_price' => 5,
            'event_date' => now()->addDay(),
            'is_active' => true,
            'status' => 'active',
        ]);
        $user = User::factory()->create();

        $ids = [];
        foreach (['000011', '000012'] as $ticketNumber) {
            $part = TicketParticipation::create([
                'user_id' => $user->id,
                'ticket_event_id' => $event->id,
                'status' => 'active',
                'payload' => ['ticket_number' => $ticketNumber, 'is_winner' => false],
            ]);
            $ids[] = $part->id;
        }

        $deleted = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/admin/ticket-events/{$event->id}/users/{$user->id}/tickets/remove", [
                'count' => 1,
            ])
            ->assertOk()
            ->json('data.deleted_ids');

        $this->assertCount(1, $deleted);
        $this->assertContains($deleted[0], $ids);
    }

    public function test_special_odd_returns_team_snapshots_from_meta(): void
    {
        $odd = \App\Models\SpecialOdd::create([
            'title' => 'PSG vs Bayern',
            'prediction' => 'Bayern Münih kazanır',
            'odds' => 10,
            'home_score' => 5,
            'away_score' => 4,
            'is_active' => true,
            'status' => 'active',
            'meta' => [
                'home_team' => ['name' => 'Paris Saint-Germain', 'logo_url' => '/storage/teams/psg.png'],
                'away_team' => ['name' => 'Bayern Münih', 'logo_url' => '/storage/teams/bayern.png'],
                'league' => ['name' => 'UEFA Şampiyonlar Ligi'],
            ],
        ]);

        $row = $this->getJson('/api/special-odds')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Paris Saint-Germain', $row['home_team']['name']);
        $this->assertSame('Bayern Münih', $row['away_team']['name']);
        $this->assertSame('UEFA Şampiyonlar Ligi', $row['league']['name']);
    }

    public function test_admin_market_orders_list_returns_paginated_orders(): void
    {
        $user = User::factory()->create([
            'name' => 'Buyer',
            'username' => 'buyer1',
            'email' => 'buyer1@example.com',
            'wallet_trc20' => 'TXabc123',
        ]);
        $product = \App\Models\MarketProduct::create([
            'title' => 'Demo',
            'description' => 'Test',
            'price' => 10,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        \App\Models\MarketOrder::create([
            'user_id' => $user->id,
            'market_product_id' => $product->id,
            'status' => 'pending',
            'payload' => ['price' => 10, 'product' => 'Demo'],
        ]);

        $admin = \App\Models\Admin::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/market/orders')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.username', 'buyer1')
            ->assertJsonPath('data.0.product.title', 'Demo')
            ->assertJsonPath('data.0.price_at_purchase', 10);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/market-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
