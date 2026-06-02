<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\PlanScheduleItem;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\ScheduleItemReviewedByStudentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleReviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_receives_database_and_fcm_notification_when_student_accepts_day_stage(): void
    {
        [$student, $trainer, $booking, $progress] = $this->createSentScheduleProgress();

        $this->withToken($student->createToken('student')->plainTextToken)
            ->postJson("/api/v1/user/subscriptions/{$booking->id}/schedule/1/accept")
            ->assertOk()
            ->assertJsonPath('data.status', UserScheduleProgress::STATUS_ACCEPTED);

        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $trainer->id)
            ->where('type', ScheduleItemReviewedByStudentNotification::class)
            ->first();

        $this->assertNotNull($storedNotification);

        $payload = json_decode((string) $storedNotification->data, true);
        $this->assertSame('schedule_item_reviewed_by_student', $payload['type']);
        $this->assertSame('تم استلام الطالبة لمرحلة اليوم', $payload['message']);
        $this->assertSame(UserScheduleProgress::STATUS_ACCEPTED, $payload['status']);
        $this->assertSame($progress->id, $payload['schedule_progress_id']);
        $this->assertSame((string) $booking->order_number, $payload['display_order_number']);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $student->id,
            'type' => ScheduleItemReviewedByStudentNotification::class,
        ]);

        $notification = new ScheduleItemReviewedByStudentNotification($progress->fresh(['userRequest.trainer', 'userRequest.user', 'planScheduleItem']));
        $channels = $notification->via($trainer);

        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);
    }

    public function test_trainer_receives_database_and_fcm_notification_when_student_rejects_day_stage(): void
    {
        [$student, $trainer, $booking] = $this->createSentScheduleProgress();

        $this->withToken($student->createToken('student')->plainTextToken)
            ->postJson("/api/v1/user/subscriptions/{$booking->id}/schedule/1/reject", [
                'reason' => 'تحتاج تعديل',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', UserScheduleProgress::STATUS_REJECTED)
            ->assertJsonPath('data.rejection_reason', 'تحتاج تعديل');

        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $trainer->id)
            ->where('type', ScheduleItemReviewedByStudentNotification::class)
            ->first();

        $this->assertNotNull($storedNotification);

        $payload = json_decode((string) $storedNotification->data, true);
        $this->assertSame('رفض استلام الطالبة لمرحلة اليوم', $payload['message']);
        $this->assertSame(UserScheduleProgress::STATUS_REJECTED, $payload['status']);
        $this->assertSame('تحتاج تعديل', $payload['rejection_reason']);
    }

    public function test_trainer_action_does_not_create_student_review_notification(): void
    {
        [, $trainer, $booking] = $this->createSentScheduleProgress();

        $this->withToken($trainer->createToken('trainer')->plainTextToken)
            ->postJson("/api/v1/user/subscriptions/{$booking->id}/schedule/1/accept")
            ->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $trainer->id,
            'type' => ScheduleItemReviewedByStudentNotification::class,
        ]);
    }

    private function createSentScheduleProgress(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Schedule Review Plan',
            'description' => 'Plan used for schedule review notification tests',
            'price_min' => 100,
            'duration_days' => '1',
            'hours_count' => 1,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $scheduleItem = PlanScheduleItem::create([
            'plan_id' => $plan->id,
            'day_number' => 1,
            'title' => 'مرحلة اليوم',
            'position' => 1,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+966500010001',
        ]);
        $trainer = User::factory()->create([
            'phone_with_cc' => '+966500010002',
            'user_type' => 'captain',
        ]);

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $progress = UserScheduleProgress::create([
            'user_request_id' => $booking->id,
            'plan_schedule_item_id' => $scheduleItem->id,
            'day_number' => 1,
            'is_checked' => true,
            'checked_at' => now(),
            'status' => UserScheduleProgress::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return [$student, $trainer, $booking, $progress];
    }
}
