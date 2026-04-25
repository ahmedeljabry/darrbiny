<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\WalletBalanceAddedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notification_sends_to_user_topic_without_reading_device_tokens(): void
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
        $messaging->shouldReceive('send')
            ->once()
            ->withArgs(function (CloudMessage $message) use ($user): bool {
                $payload = $message->jsonSerialize();

                return ($payload['topic'] ?? null) === 'user_'.$user->id
                    && ($payload['notification']['title'] ?? null) === 'تم إضافة رصيد إلى محفظتك'
                    && ($payload['data']['notification_type'] ?? null) === 'wallet_balance_added';
            })
            ->andReturn(['name' => 'projects/test/messages/1']);

        $this->app->instance(Messaging::class, $messaging);

        $user->notify(new WalletBalanceAddedNotification(25));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => WalletBalanceAddedNotification::class,
        ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'token' => 'valid-token',
        ]);
        $this->assertDatabaseHas('user_device_tokens', [
            'token' => 'invalid-token',
        ]);
    }
}
