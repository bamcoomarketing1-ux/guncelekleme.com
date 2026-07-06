<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SocialMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SocialMediaAdminTest extends TestCase
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

    public function test_social_media_store_maps_frontend_fields(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/social-media', [
                'name' => 'Emir Laz Sohbet',
                'type' => 'telegram',
                'url' => 'https://t.me/EmirLazSohbet',
                'order' => 0,
                'is_active' => true,
                'show_on_homepage' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('social_media.platform', 'telegram')
            ->assertJsonPath('social_media.title', 'Emir Laz Sohbet')
            ->assertJsonPath('social_media.type', 'telegram')
            ->assertJsonPath('social_media.name', 'Emir Laz Sohbet');

        $this->assertDatabaseHas('social_media', [
            'platform' => 'telegram',
            'title' => 'Emir Laz Sohbet',
            'url' => 'https://t.me/EmirLazSohbet',
        ]);
    }

    public function test_social_media_update_maps_frontend_fields(): void
    {
        $admin = $this->admin();
        $item = SocialMedia::create([
            'platform' => null,
            'title' => null,
            'url' => 'https://t.me/EmirLazSohbet',
            'is_active' => true,
            'show_on_homepage' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/social-media/{$item->id}", [
                'name' => 'Telegram Kanal',
                'type' => 'telegram',
                'url' => 'https://t.me/EmirLazSohbet',
                'order' => 1,
                'is_active' => true,
                'show_on_homepage' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.platform', 'telegram')
            ->assertJsonPath('data.title', 'Telegram Kanal');
    }
}
