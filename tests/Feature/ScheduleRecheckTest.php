<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\PlanScheduleItem;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use App\Modules\Requests\Services\UserScheduleService;
use App\Notifications\ScheduleItemSentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduleRecheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_recheck_rejected_day_and_resend_for_approval(): void
    {
        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'currency' => 'USD',
        ]);
        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Test plan',
            'price_min' => 100,
            'duration_days' => '1',
            'hours_count' => 1,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $scheduleItem = PlanScheduleItem::create([
            'plan_id' => $plan->id,
            'day_number' => 1,
            'title' => 'Day 1',
            'position' => 1,
        ]);

        $student = User::factory()->create(['phone_with_cc' => '+10000000101']);
        $trainer = User::factory()->create(['phone_with_cc' => '+10000000102']);
        $request = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);
        $progress = UserScheduleProgress::create([
            'user_request_id' => $request->id,
            'plan_schedule_item_id' => $scheduleItem->id,
            'day_number' => 1,
            'is_checked' => true,
            'checked_at' => now()->subDay(),
            'status' => UserScheduleProgress::STATUS_REJECTED,
            'rejection_reason' => 'Needs correction',
        ]);

        Notification::fake();

        $updated = app(UserScheduleService::class)->checkDay($request, 1, $trainer);

        $this->assertTrue($updated->is_checked);
        $this->assertSame(UserScheduleProgress::STATUS_SENT, $updated->status);
        $this->assertNull($updated->rejection_reason);
        $this->assertNotNull($updated->sent_at);

        $progress->refresh();
        $this->assertSame(UserScheduleProgress::STATUS_SENT, $progress->status);
        $this->assertNull($progress->rejection_reason);
        Notification::assertSentTo(
            $student,
            ScheduleItemSentNotification::class,
            function (ScheduleItemSentNotification $notification) use ($request, $student): bool {
                $payload = $notification->toDatabase($student);

                $this->assertSame($request->order_number, $payload['order_number']);
                $this->assertSame($request->formatted_order_number, $payload['formatted_order_number']);
                $this->assertSame((string) $request->order_number, $payload['display_order_number']);
                $this->assertStringStartsWith('5', $payload['display_order_number']);

                return true;
            }
        );
    }
}
