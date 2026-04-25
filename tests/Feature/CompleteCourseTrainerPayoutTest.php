<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Payout;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use App\Notifications\CourseCompletedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompleteCourseTrainerPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_completion_adds_net_amount_to_existing_trainer_wallet_balance_and_does_not_duplicate(): void
    {
        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009001',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009002',
            'user_type' => 'captain',
            'points_balance' => 20,
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

        $this->assertSame(101, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 8100,
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

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'type' => CourseCompletedNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $trainer->id,
            'type' => CourseCompletedNotification::class,
        ]);
        $this->assertSame(2, DB::table('notifications')
            ->where('type', CourseCompletedNotification::class)
            ->whereIn('notifiable_id', [$student->id, $trainer->id])
            ->count());

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk();

        $this->assertSame(101, (int) $trainer->fresh()->points_balance);
        $this->assertSame(1, WalletTransaction::query()
            ->where('user_id', $trainer->id)
            ->where('notes', 'إضافة مستحقات كورس رقم ' . $booking->id)
            ->count());
        $this->assertSame(1, Payout::query()
            ->where('user_request_id', $booking->id)
            ->count());
        $this->assertSame(2, DB::table('notifications')
            ->where('type', CourseCompletedNotification::class)
            ->whereIn('notifiable_id', [$student->id, $trainer->id])
            ->count());
    }

    public function test_user_completion_uses_app_fee_setting_when_payment_fee_fields_are_missing(): void
    {
        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '10',
        ]);

        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009006',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009007',
            'user_type' => 'captain',
            'points_balance' => 40,
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

        $payment = Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 0,
        ]);

        $token = $student->createToken('complete-course-setting-fee')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', UserRequest::STATUS_COMPLETED);

        $this->assertSame(130, (int) $trainer->fresh()->points_balance);

        $this->assertSame(1000, (int) $payment->fresh()->app_fee_minor);
        $this->assertSame(9000, (int) $payment->fresh()->trainer_net_minor);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 9000,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'إضافة مستحقات كورس رقم ' . $booking->id,
            'processed_by' => $student->id,
        ]);

        $this->assertDatabaseHas('payouts', [
            'trainer_id' => $trainer->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 9000,
            'currency' => 'SAR',
            'status' => Payout::STATUS_PENDING_REVIEW,
        ]);
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
            'amount' => 9400,
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

    public function test_completion_recalculates_app_fee_when_legacy_full_payment_stored_gross_trainer_net(): void
    {
        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '10',
        ]);

        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009008',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009009',
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

        $payment = Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 10000,
        ]);

        $token = $student->createToken('complete-course-legacy-gross')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', UserRequest::STATUS_COMPLETED);

        $this->assertSame(90, (int) $trainer->fresh()->points_balance);
        $this->assertSame(1000, (int) $payment->fresh()->app_fee_minor);
        $this->assertSame(9000, (int) $payment->fresh()->trainer_net_minor);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 9000,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'إضافة مستحقات كورس رقم ' . $booking->id,
            'processed_by' => $student->id,
        ]);
    }

    public function test_completion_keeps_fractional_trainer_wallet_credit_after_app_fee(): void
    {
        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '10',
        ]);

        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'phone_with_cc' => '+10000009010',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009011',
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

        $payment = Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 100,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 10,
            'trainer_net_minor' => 90,
        ]);

        $token = $student->createToken('complete-course-fractional')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/user-requests/{$booking->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', UserRequest::STATUS_COMPLETED);

        $this->assertEquals(0.90, $trainer->fresh()->points_balance);
        $this->assertSame(10, (int) $payment->fresh()->app_fee_minor);
        $this->assertSame(90, (int) $payment->fresh()->trainer_net_minor);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 90,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'إضافة مستحقات كورس رقم ' . $booking->id,
            'processed_by' => $student->id,
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
