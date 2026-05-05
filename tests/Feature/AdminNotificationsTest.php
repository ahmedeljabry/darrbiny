<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\AdminMessageNotification;
use App\Notifications\Channels\FcmChannel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_send_notification_to_trainers_uses_token_channel_even_without_registered_devices(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008001',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_notifications');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000008002',
        ]);
        $trainer->assignRole('TRAINER');

        $this->actingAs($admin)
            ->post(route('admin.notifications.send'), [
                'audience' => 'trainers',
                'title' => 'تنبيه',
                'message' => 'رسالة اختبار',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إرسال الإشعار إلى 1 مستخدمين. الأجهزة المستهدفة: 0')
            ->assertSessionMissing('warning');

        Notification::assertSentTo(
            $trainer,
            AdminMessageNotification::class,
            fn (AdminMessageNotification $notification, array $channels): bool => $notification->title === 'تنبيه'
                && $notification->message === 'رسالة اختبار'
                && in_array('database', $channels, true)
                && in_array(FcmChannel::class, $channels, true)
        );
    }

    public function test_admin_send_notification_reports_registered_devices_for_single_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();

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
            ->assertSessionHas('status', 'تم إرسال الإشعار إلى 1 مستخدمين. الأجهزة المستهدفة: 1');

        Notification::assertSentTo(
            $user,
            AdminMessageNotification::class,
            fn (AdminMessageNotification $notification, array $channels): bool => $notification->title === 'تنبيه'
                && $notification->message === 'رسالة اختبار'
                && in_array('database', $channels, true)
                && in_array(FcmChannel::class, $channels, true)
        );
    }

    public function test_admin_can_search_notification_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008005',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_notifications');

        $matchingUser = User::factory()->create([
            'name' => 'Maher Salem',
            'phone_with_cc' => '+201234567890',
        ]);

        User::factory()->create([
            'name' => 'Another User',
            'phone_with_cc' => '+10000008006',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.users', ['q' => 'Maher']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $matchingUser->id,
                'text' => 'Maher Salem — +201234567890',
            ]);
    }
}
