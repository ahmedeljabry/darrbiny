<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\AdminMessageNotification;
use App\Notifications\CourseCancelledNotification;
use App\Notifications\NewRequestAvailable;
use App\Notifications\ReferralPointsAddedNotification;
use App\Notifications\SupportTicketReplyNotification;
use App\Notifications\WalletBalanceAddedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationBadgesEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_badges_endpoint_returns_counts_for_app_icons(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000004001',
            'email' => 'badges-user@example.com',
        ]);
        $user->assignRole('USER');

        $otherUser = User::factory()->create([
            'phone_with_cc' => '+10000004002',
            'email' => 'badges-other@example.com',
        ]);
        $otherUser->assignRole('USER');

        $conversation = Conversation::create([
            'user_one_id' => $user->id,
            'user_two_id' => $otherUser->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $otherUser->id,
            'message' => 'New unread message',
            'is_read' => false,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => 'My own message',
            'is_read' => false,
        ]);

        $this->insertNotification($user, NewRequestAvailable::class, [
            'title' => 'طلب جديد',
            'message' => 'هناك طلب جديد متاح',
        ]);
        $this->insertNotification($user, SupportTicketReplyNotification::class, [
            'title' => 'رد جديد على تذكرة الدعم',
            'message' => 'تم الرد على تذكرتك',
        ]);
        $this->insertNotification($user, WalletBalanceAddedNotification::class, [
            'title' => 'تم إضافة رصيد إلى محفظتك',
            'amount' => 25,
        ]);
        $this->insertNotification($user, ReferralPointsAddedNotification::class, [
            'title' => 'تم إضافة نقاط إلى مكافآتي',
            'points' => 1,
        ]);
        $this->insertNotification($user, CourseCancelledNotification::class, [
            'title' => 'تم إلغاء الدورة',
            'refund_amount' => 50,
        ]);
        $this->insertNotification($user, AdminMessageNotification::class, [
            'title' => 'تنبيه عام',
            'message' => 'رسالة عامة',
        ]);

        // Read notification should not affect counters.
        $this->insertNotification(
            $user,
            WalletBalanceAddedNotification::class,
            ['title' => 'Old wallet credit', 'amount' => 10],
            now()->toIso8601String()
        );

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications/badges')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notifications.count', 6)
            ->assertJsonPath('data.notifications.has_unread', true)
            ->assertJsonPath('data.messages.count', 1)
            ->assertJsonPath('data.messages.has_unread', true)
            ->assertJsonPath('data.trainings.count', 2)
            ->assertJsonPath('data.trainings.has_unread', true)
            ->assertJsonPath('data.support_tickets.count', 1)
            ->assertJsonPath('data.support_tickets.has_unread', true)
            ->assertJsonPath('data.wallet.count', 2)
            ->assertJsonPath('data.wallet.has_unread', true)
            ->assertJsonPath('data.rewards.count', 1)
            ->assertJsonPath('data.rewards.has_unread', true)
            ->assertJsonPath('data.account.count', 4)
            ->assertJsonPath('data.account.has_unread', true);
    }

    public function test_badges_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/notifications/badges')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    private function insertNotification(User $user, string $type, array $data, ?string $readAt = null): void
    {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
