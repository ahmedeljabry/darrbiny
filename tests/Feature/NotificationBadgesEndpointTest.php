<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Country;
use App\Models\Message;
use App\Models\Plan;
use App\Models\SupportTicket;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
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
        $user->assignRole('TRAINER');

        $otherUser = User::factory()->create([
            'phone_with_cc' => '+10000004002',
            'email' => 'badges-other@example.com',
        ]);
        $otherUser->assignRole('USER');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Badge Plan',
            'description' => 'Plan for notification badges test',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user->trainerProfile()->create([
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Trainer Locality',
        ]);

        $studentRequest = UserRequest::create([
            'user_id' => $user->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Jeddah Province',
            'area_level_2' => 'Jeddah',
            'area_level_3' => 'West',
            'locality' => 'Student Locality',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        $assignedTrainerRequest = UserRequest::create([
            'user_id' => $otherUser->id,
            'trainer_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Dammam Province',
            'area_level_2' => 'Dammam',
            'area_level_3' => 'East',
            'locality' => 'Assigned Locality',
            'start_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        $openMatchedRequest = UserRequest::create([
            'user_id' => $otherUser->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Open Locality',
            'start_date' => now()->addDays(3)->toDateString(),
            'start_time' => '11:00:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        UserRequest::create([
            'user_id' => $otherUser->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => null,
            'locality' => 'Open Missing District',
            'start_date' => now()->addDays(4)->toDateString(),
            'start_time' => '11:30:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        $unmatchedOpenRequest = UserRequest::create([
            'user_id' => $otherUser->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'South',
            'locality' => 'Unmatched Locality',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '12:00:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        $trainerOne = User::factory()->create([
            'phone_with_cc' => '+10000004003',
            'email' => 'badges-trainer-one@example.com',
        ]);
        $trainerTwo = User::factory()->create([
            'phone_with_cc' => '+10000004004',
            'email' => 'badges-trainer-two@example.com',
        ]);

        TrainerOffer::create([
            'user_request_id' => $studentRequest->id,
            'trainer_id' => $trainerOne->id,
            'price_minor' => 25000,
            'message' => 'First offer',
            'status' => TrainerOffer::STATUS_SENT,
        ]);
        TrainerOffer::create([
            'user_request_id' => $studentRequest->id,
            'trainer_id' => $trainerTwo->id,
            'price_minor' => 27000,
            'message' => 'Second offer',
            'status' => TrainerOffer::STATUS_SENT,
        ]);
        TrainerOffer::create([
            'user_request_id' => $assignedTrainerRequest->id,
            'trainer_id' => $trainerTwo->id,
            'price_minor' => 29000,
            'message' => 'Not my offer count',
            'status' => TrainerOffer::STATUS_SENT,
        ]);
        TrainerOffer::create([
            'user_request_id' => $unmatchedOpenRequest->id,
            'trainer_id' => $trainerOne->id,
            'price_minor' => 31000,
            'message' => 'Not my booking count',
            'status' => TrainerOffer::STATUS_SENT,
        ]);

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

        SupportTicket::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'Existing support ticket',
            'status' => 'pending',
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
            ->assertJsonPath('data.account.has_unread', true)
            ->assertJsonPath('data.offers.count', 2)
            ->assertJsonPath('data.offers.has_unread', true)
            ->assertJsonPath('data.bookings.count', 3)
            ->assertJsonPath('data.bookings.has_unread', true);
    }

    public function test_badges_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/notifications/badges')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_badges_endpoint_counts_only_in_progress_support_tickets(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000004005',
            'email' => 'badges-support-only@example.com',
        ]);
        $user->assignRole('USER');

        SupportTicket::create([
            'user_id' => null,
            'name' => $user->name,
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'Open support ticket',
            'status' => 'open',
        ]);

        SupportTicket::create([
            'user_id' => null,
            'name' => $user->name,
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'In progress support ticket',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications/badges')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notifications.count', 0)
            ->assertJsonPath('data.support_tickets.count', 1)
            ->assertJsonPath('data.support_tickets.has_unread', true)
            ->assertJsonPath('data.account.count', 1)
            ->assertJsonPath('data.account.has_unread', true);
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
