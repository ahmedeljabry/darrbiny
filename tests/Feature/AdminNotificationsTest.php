<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_send_notification_to_trainers_uses_topic_even_without_registered_devices(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')
            ->once()
            ->withArgs(function (CloudMessage $message): bool {
                $payload = $message->jsonSerialize();

                return ($payload['topic'] ?? null) === 'trainers'
                    && ($payload['notification']['title'] ?? null) === 'تنبيه'
                    && ($payload['notification']['body'] ?? null) === 'رسالة اختبار';
            })
            ->andReturn(['name' => 'projects/test/messages/1']);
        $this->app->instance(Messaging::class, $messaging);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008001',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_notifications');

        User::factory()->create([
            'phone_with_cc' => '+10000008002',
        ])->assignRole('TRAINER');

        $this->actingAs($admin)
            ->post(route('admin.notifications.send'), [
                'audience' => 'trainers',
                'title' => 'تنبيه',
                'message' => 'رسالة اختبار',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إرسال الإشعار إلى 1 مستخدمين عبر Topic')
            ->assertSessionMissing('warning');
    }

    public function test_admin_send_notification_reports_registered_devices_for_single_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')
            ->once()
            ->withArgs(function (CloudMessage $message) use (&$user): bool {
                $payload = $message->jsonSerialize();

                return ($payload['topic'] ?? null) === 'user_'.$user->id
                    && ($payload['notification']['title'] ?? null) === 'تنبيه'
                    && ($payload['notification']['body'] ?? null) === 'رسالة اختبار';
            })
            ->andReturn(['name' => 'projects/test/messages/2']);
        $this->app->instance(Messaging::class, $messaging);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008003',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_notifications');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000008004',
        ]);
        $user->assignRole('USER');

        UserDeviceToken::create([
            'user_id' => $user->id,
            'token' => 'device-token-1',
            'platform' => 'android',
            'device_name' => 'Pixel',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.notifications.send'), [
                'audience' => 'user',
                'user_id' => $user->id,
                'title' => 'تنبيه',
                'message' => 'رسالة اختبار',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إرسال الإشعار إلى 1 مستخدمين عبر Topic');
    }
}
