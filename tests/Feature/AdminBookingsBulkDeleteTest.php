<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Rating;
use App\Models\TrainerOffer;
use App\Models\TrainingDay;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminBookingsBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    private int $phoneSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_bookings_index_exposes_bulk_delete_checkboxes(): void
    {
        $admin = $this->createAdmin();
        $this->createBookingGraph('Visible Bulk Booking', UserRequest::STATUS_IN_TRAINING);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('id="booking-select-all"', false)
            ->assertSee('id="booking-select-all-toolbar"', false)
            ->assertSee('name="booking_ids[]"', false)
            ->assertSee(route('admin.bookings.bulk-destroy'), false)
            ->assertSee('حذف المحدد');
    }

    public function test_admin_can_bulk_delete_bookings_with_related_report_data(): void
    {
        $admin = $this->createAdmin();

        $selectedOne = $this->createBookingGraph('Selected Booking One', UserRequest::STATUS_IN_TRAINING, 11_000);
        $selectedTwo = $this->createBookingGraph('Selected Booking Two', UserRequest::STATUS_COMPLETED, 12_000);
        $remaining = $this->createBookingGraph('Remaining Booking', UserRequest::STATUS_IN_TRAINING, 22_000);

        $selectedOneRawOrderNumber = '#'.$selectedOne->display_order_number;
        $selectedTwoRawOrderNumber = '#'.$selectedTwo->display_order_number;
        $remainingRawOrderNumber = '#'.$remaining->display_order_number;
        $selectedOneFormattedOrderNumber = '#'.$selectedOne->formatted_order_number;
        $selectedTwoFormattedOrderNumber = '#'.$selectedTwo->formatted_order_number;
        $remainingFormattedOrderNumber = '#'.$remaining->formatted_order_number;

        $this->actingAs($admin)
            ->delete(route('admin.bookings.bulk-destroy'), [
                'booking_ids' => [
                    $selectedOne->id,
                    $selectedTwo->id,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'تم حذف 2 حجز بنجاح وتحديث التقارير المرتبطة.');

        foreach ([$selectedOne, $selectedTwo] as $deletedBooking) {
            $this->assertDatabaseMissing('user_requests', ['id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('payments', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('trainer_offers', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('training_days', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('user_schedule_progress', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('cancellation_requests', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('payouts', ['user_request_id' => $deletedBooking->id]);
            $this->assertDatabaseMissing('ratings', ['user_request_id' => $deletedBooking->id]);
        }

        $this->assertDatabaseHas('user_requests', ['id' => $remaining->id]);
        $this->assertDatabaseHas('payments', ['user_request_id' => $remaining->id, 'amount_minor' => 22_000]);
        $this->assertDatabaseCount('user_requests', 1);
        $this->assertDatabaseCount('payments', 1);

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertDontSee($selectedOneRawOrderNumber)
            ->assertDontSee($selectedTwoRawOrderNumber)
            ->assertSee($remainingRawOrderNumber);

        foreach ([route('admin.reports.sales'), route('admin.reports.payments'), route('admin.reports.subscriptions')] as $reportUrl) {
            $this->actingAs($admin)
                ->get($reportUrl)
                ->assertOk()
                ->assertDontSee($selectedOneFormattedOrderNumber)
                ->assertDontSee($selectedTwoFormattedOrderNumber)
                ->assertSee($remainingFormattedOrderNumber);
        }
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008000',
            'email' => 'admin-booking-bulk-delete@example.com',
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
    }

    private function createBookingGraph(string $studentName, string $status, int $paymentAmountMinor = 10_000): UserRequest
    {
        $country = Country::query()->firstOrCreate(
            ['iso2' => 'SA'],
            ['name' => 'Saudi Arabia', 'currency' => 'SAR']
        );

        $plan = Plan::create([
            'title' => $studentName.' Plan',
            'description' => 'Plan for booking bulk delete tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'name' => $studentName,
            'phone_with_cc' => $this->nextPhoneNumber('5'),
        ]);

        $trainer = User::factory()->create([
            'name' => $studentName.' Trainer',
            'phone_with_cc' => $this->nextPhoneNumber('6'),
        ]);

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => $status,
            'currency' => 'SAR',
            'total_paid_minor' => $paymentAmountMinor,
            'app_fee_reserved_minor' => 1_000,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => $paymentAmountMinor,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => Payment::METHOD_WALLET,
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => $paymentAmountMinor - 1_000,
        ]);

        TrainerOffer::create([
            'user_request_id' => $booking->id,
            'trainer_id' => $trainer->id,
            'price_minor' => $paymentAmountMinor,
            'message' => 'Bulk delete offer',
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);

        TrainingDay::create([
            'user_request_id' => $booking->id,
            'trainer_id' => $trainer->id,
            'date' => now()->toDateString(),
            'hours_done' => 2,
            'notes' => 'Bulk delete training day',
            'status' => TrainingDay::STATUS_SUBMITTED,
        ]);

        UserScheduleProgress::create([
            'user_request_id' => $booking->id,
            'plan_schedule_item_id' => (string) Str::uuid(),
            'day_number' => 1,
            'is_checked' => true,
            'checked_at' => now(),
            'status' => UserScheduleProgress::STATUS_ACCEPTED,
        ]);

        CancellationRequest::create([
            'user_request_id' => $booking->id,
            'user_id' => $student->id,
            'reason' => 'Bulk delete cancellation',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 500,
        ]);

        Payout::create([
            'trainer_id' => $trainer->id,
            'user_request_id' => $booking->id,
            'amount_minor' => $paymentAmountMinor - 1_000,
            'currency' => 'SAR',
            'status' => Payout::STATUS_SENT,
            'processed_at' => now(),
        ]);

        Rating::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'user_request_id' => $booking->id,
            'stars' => 5,
            'comment' => 'Bulk delete rating',
        ]);

        return $booking;
    }

    private function nextPhoneNumber(string $prefix): string
    {
        return '+966'.$prefix.str_pad((string) ++$this->phoneSequence, 8, '0', STR_PAD_LEFT);
    }
}
