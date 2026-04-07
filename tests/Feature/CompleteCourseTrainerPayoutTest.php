<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Payout;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteCourseTrainerPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_completion_credits_trainer_wallet_from_payment_net_and_does_not_duplicate(): void
    {
        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009001',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009002',
            'user_type' => 'captain',
            'points_balance' => 0,
        ]);
        $trainer->assignRole('TRAINER');

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 9000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 900,
            'trainer_net_minor' => 8100,
        ]);

        $token = $student->createToken('complete-course')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', UserRequest::STATUS_COMPLETED);

        $this->assertSame(81, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 81,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'إضافة مستحقات كورس رقم ' . $booking->id,
            'processed_by' => $student->id,
        ]);

        $this->assertDatabaseHas('payouts', [
            'trainer_id' => $trainer->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 8100,
            'currency' => 'SAR',
            'status' => Payout::STATUS_PENDING_REVIEW,
        ]);

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk();

        $this->assertSame(81, (int) $trainer->fresh()->points_balance);
        $this->assertSame(1, WalletTransaction::query()
            ->where('user_id', $trainer->id)
            ->where('notes', 'إضافة مستحقات كورس رقم ' . $booking->id)
            ->count());
        $this->assertSame(1, Payout::query()
            ->where('user_request_id', $booking->id)
            ->count());
    }

    public function test_admin_marking_booking_completed_uses_same_trainer_wallet_credit_flow(): void
    {
        [, $plan] = $this->createLocationPlan();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009003',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_plans');

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009004',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009005',
            'user_type' => 'captain',
            'points_balance' => 0,
        ]);
        $trainer->assignRole('TRAINER');

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 600,
            'trainer_net_minor' => 9400,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update-status', $booking->id), [
                'status' => UserRequest::STATUS_COMPLETED,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم تحديث حالة الحجز من in_training إلى completed');

        $this->assertSame(94, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 94,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'إضافة مستحقات كورس رقم ' . $booking->id,
            'processed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('payouts', [
            'trainer_id' => $trainer->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 9400,
            'currency' => 'SAR',
            'status' => Payout::STATUS_PENDING_REVIEW,
        ]);
    }

    /**
     * @return array{0: Country, 1: Plan}
     */
    private function createLocationPlan(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Driving Plan',
            'description' => 'Trainer payout completion test plan',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        return [$country, $plan];
    }
}
