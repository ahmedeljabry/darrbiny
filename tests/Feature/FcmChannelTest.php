<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\WalletBalanceAddedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notification_sends_to_fcm_and_removes_invalid_tokens(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005003',
        ]);
        $user->assignRole('USER');

        UserDeviceToken::create([
            'user_id' => $user->id,
            'token' => 'valid-token',
            'platform' => 'android',
            'last_used_at' => now(),
        ]);

        UserDeviceToken::create([
            'user_id' => $user->id,
            'token' => 'invalid-token',
            'platform' => 'ios',
            'last_used_at' => now(),
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'valid-token'), ['name' => 'projects/test/messages/1']),
                SendReport::failure(
                    MessageTarget::with(MessageTarget::TOKEN, 'invalid-token'),
                    new RuntimeException('invalid token')
                ),
            ]));

        $this->app->instance(Messaging::class, $messaging);

        $user->notify(new WalletBalanceAddedNotification(25));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => WalletBalanceAddedNotification::class,
        ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'token' => 'valid-token',
        ]);

        $this->assertDatabaseMissing('user_device_tokens', [
            'token' => 'invalid-token',
        ]);
    }
}
