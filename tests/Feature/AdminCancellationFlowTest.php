<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
    }

    public function test_admin_cancelling_booking_creates_approved_cancellation_request_record(): void
    {
        $admin = $this->createAdmin();
        [$plan, $user] = $this->createPlanAndUser();

        $booking = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->addDay()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.cancel', $booking->id), [
                'refund_amount' => 50,
                'reason' => 'إلغاء إداري للدورة',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_requests', [
            'id' => $booking->id,
            'status' => UserRequest::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('cancellation_requests', [
            'user_request_id' => $booking->id,
            'user_id' => $user->id,
            'status' => CancellationRequest::STATUS_APPROVED,
            'processed_by' => $admin->id,
        ]);
    }

    public function test_approving_cancellation_request_refunds_sum_of_successful_payments(): void
    {
        $admin = $this->createAdmin();
        [$plan, $user] = $this->createPlanAndUser();

        $booking = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->addDay()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 2_000,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 2_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 2_000,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 8_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 800,
            'trainer_net_minor' => 7_200,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 5_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_FAILED,
            'app_fee_minor' => 500,
            'trainer_net_minor' => 4_500,
        ]);

        $cancellation = CancellationRequest::create([
            'user_request_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => 'طلب إلغاء',
            'status' => CancellationRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cancellation-requests.approve', $cancellation->id), [
                'admin_notes' => 'موافقة الإدارة',
            ])
            ->assertRedirect();

        $this->assertSame(100, $user->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
        ]);

        $this->assertDatabaseHas('cancellation_requests', [
            'id' => $cancellation->id,
            'status' => CancellationRequest::STATUS_APPROVED,
            'processed_by' => $admin->id,
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009000',
            'email' => 'admin-cancellations@example.com',
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
    }

    /**
     * @return array{0: Plan, 1: User}
     */
    private function createPlanAndUser(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $city = City::create([
            'name' => 'Riyadh',
            'country_id' => $country->id,
        ]);

        $plan = Plan::create([
            'title' => 'Cancellation Plan',
            'description' => 'Plan for admin cancellation tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009001',
            'currency' => 'SAR',
            'points_balance' => 0,
        ]);

        return [$plan, $user];
    }
}
