<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Sponsor;
use App\Models\TicketEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformCompatFixTest extends TestCase
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

    public function test_statistics_endpoint_returns_summary_and_charts(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/statistics?period=7')
            ->assertOk()
            ->json();

        $this->assertSame('success', $response['status']);
        $this->assertArrayHasKey('summary', $response['data']);
        $this->assertArrayHasKey('charts', $response['data']);
        $this->assertArrayHasKey('active_this_period', $response['data']['summary']);
        $this->assertCount(7, $response['data']['charts']['labels']);
    }

    public function test_ticket_event_persists_sponsor_id(): void
    {
        $sponsor = Sponsor::create(['name' => 'BetKral', 'logo_url' => '/storage/test.png', 'is_active' => true]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/ticket-events', [
                'sponsor_id' => $sponsor->id,
                'title' => 'Test Event',
                'ticket_price' => 100,
                'total_tickets' => 50,
            ])
            ->assertCreated();

        $event = TicketEvent::first();
        $this->assertSame($sponsor->id, $event->sponsor_id);

        $payload = $this->getJson('/api/ticket-events')->assertOk()->json('data.0');
        $this->assertSame($sponsor->id, $payload['sponsor_id']);
        $this->assertSame('BetKral', $payload['sponsor']['name']);
    }

    public function test_market_reorder_accepts_ids_payload(): void
    {
        $first = \App\Models\MarketProduct::create(['title' => 'A', 'price' => 10, 'sort_order' => 1]);
        $second = \App\Models\MarketProduct::create(['title' => 'B', 'price' => 20, 'sort_order' => 2]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/market/reorder', ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
    }
}
