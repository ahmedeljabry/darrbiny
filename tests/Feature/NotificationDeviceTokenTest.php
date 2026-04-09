<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authenticated_user_can_store_device_token(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005001',
        ]);
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/notifications/devices', [
            'token' => 'fcm-token-1',
            'platform' => 'android',
            'device_name' => 'Pixel 9',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.device_name', 'Pixel 9');

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-1',
            'platform' => 'android',
            'device_name' => 'Pixel 9',
        ]);
    }

    public function test_authenticated_user_can_delete_own_device_token(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005002',
        ]);
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/notifications/devices', [
            'token' => 'fcm-token-2',
            'platform' => 'ios',
        ])->assertOk();

        $this->deleteJson('/api/v1/notifications/devices', [
            'token' => 'fcm-token-2',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('user_device_tokens', [
            'token' => 'fcm-token-2',
        ]);
    }
}
