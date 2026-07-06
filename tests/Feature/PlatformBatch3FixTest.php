<?php

namespace Tests\Feature;

use App\Models\MarketProduct;
use App\Models\Notification;
use App\Models\Sponsor;
use App\Models\TicketEvent;
use App\Models\User;
use App\Models\UserSponsor;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlatformBatch3FixTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['balance' => 1000]);
    }

    public function test_ticket_event_show_returns_detail_payload(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Test Sponsor',
            'logo_url' => '/storage/sponsors/logo.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $event = TicketEvent::create([
            'sponsor_id' => $sponsor->id,
            'title' => 'Summer Event',
            'description' => 'Event description text',
            'total_tickets' => 100,
            'ticket_price' => 50,
            'event_date' => now()->addWeek(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $user = $this->user();
        UserSponsor::create([
            'user_id' => $user->id,
            'sponsor_id' => $sponsor->id,
            'username' => 'player1',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/ticket-events/{$event->id}")
            ->assertOk()
            ->json();

        $this->assertSame('success', $response['status']);
        $this->assertSame('Event description text', $response['data']['event']['description']);
        $this->assertSame('active', $response['data']['event']['status']);
        $this->assertTrue($response['data']['has_sponsor_linked']);
        $this->assertArrayHasKey('user_tickets', $response['data']);
        $this->assertArrayHasKey('user_requests', $response['data']);
    }

    public function test_ticket_event_request_accepts_multipart_form(): void
    {
        $this->mock(UploadService::class, function ($mock): void {
            $mock->shouldReceive('storeImage')
                ->once()
                ->andReturn('/storage/ticket-requests/test.jpg');
        });

        $event = TicketEvent::create([
            'title' => 'Open Event',
            'description' => 'No sponsor required',
            'total_tickets' => 50,
            'ticket_price' => 10,
            'event_date' => now()->addDays(3),
            'is_active' => true,
            'status' => 'active',
        ]);

        $user = $this->user();
        $file = UploadedFile::fake()->image('proof.jpg');

        $this->actingAs($user, 'sanctum')
            ->post("/api/ticket-events/{$event->id}/request", [
                'investment_amount' => 250,
                'screenshot' => $file,
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('ticket_requests', [
            'user_id' => $user->id,
            'ticket_event_id' => $event->id,
            'status' => 'pending',
        ]);
    }

    public function test_notifications_return_announcement_shape(): void
    {
        $user = $this->user();

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test body content',
            'type' => 'success',
            'is_read' => false,
        ]);

        $item = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Test Title', $item['announcement']['title']);
        $this->assertSame('Test body content', $item['announcement']['content']);
        $this->assertSame('success', $item['announcement']['type']);
        $this->assertFalse($item['is_read']);
    }

    public function test_market_product_normalizes_image_paths(): void
    {
        $product = MarketProduct::create([
            'title' => 'Gift Card',
            'description' => 'Test',
            'price' => 99.99,
            'image_path' => 'market/item.png',
            'required_wallets' => ['trc20'],
            'is_active' => true,
        ]);

        $row = $product->toApiArray();

        $this->assertSame('/storage/market/item.png', $row['image_path']);
        $this->assertSame('/storage/market/item.png', $row['image']);
        $this->assertSame(['trc20'], $row['required_wallets']);
    }

    public function test_admin_market_store_persists_uploaded_image_path(): void
    {
        UploadService::ensurePublicStorage();

        $admin = \App\Models\Admin::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $file = UploadedFile::fake()->image('product.jpg', 200, 200);

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/admin/market', [
                'title' => 'Demo',
                'description' => 'Test',
                'price' => 10,
                'is_active' => 1,
                'image' => $file,
            ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $product = MarketProduct::where('title', 'Demo')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith('/storage/market/', $product->image_path);
    }
}
