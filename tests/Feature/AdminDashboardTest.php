<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppExpense;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_shows_updated_labels_and_fee_totals(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009001',
            'email' => 'admin-dashboard@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Dashboard Plan',
            'description' => 'Plan used for dashboard totals',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009002',
        ]);

        $activeRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_CANCELLED,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $activeRequest->id,
            'amount_minor' => 12345,
            'currency' => 'SAR',
            'type' => Payment::TYPE_RESERVATION_FEE,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 12345,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $activeRequest->id,
            'amount_minor' => 23456,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 23456,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $activeRequest->id,
            'amount_minor' => 34567,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 3456,
            'trainer_net_minor' => 31111,
        ]);

        AppExpense::create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 8000,
            'notes' => 'Dashboard operating expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('إجمالي المبيعات')
            ->assertSee('رسوم الحجز')
            ->assertSee('رسوم الباقات')
            ->assertSee('المصروفات')
            ->assertSee('رصيد محفظة التطبيق')
            ->assertSee('قيمة الحجوزات')
            ->assertSee('صافي الربح')
            ->assertSee('قيد التدريب')
            ->assertSee('الكورسات الملغاة')
            ->assertSee('703.68')
            ->assertSee('123.45')
            ->assertSee('580.23')
            ->assertSee('80.00')
            ->assertSee('345.67')
            ->assertSee('623.68');
    }

    public function test_dashboard_supports_custom_date_range_filter(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009011',
            'email' => 'admin-dashboard-range@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Dashboard Range Plan',
            'description' => 'Plan used for dashboard range filter',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        Carbon::setTestNow('2026-01-10 12:00:00');
        $januaryUser = User::factory()->create([
            'phone_with_cc' => '+10000009012',
        ]);
        $januaryRequest = UserRequest::create([
            'user_id' => $januaryUser->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        Payment::create([
            'user_id' => $januaryUser->id,
            'user_request_id' => $januaryRequest->id,
            'amount_minor' => 11111,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1111,
            'trainer_net_minor' => 10000,
        ]);
        AppExpense::create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 1000,
            'notes' => 'January expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Carbon::setTestNow('2026-02-10 12:00:00');
        $februaryUser = User::factory()->create([
            'phone_with_cc' => '+10000009013',
        ]);
        $februaryRequest = UserRequest::create([
            'user_id' => $februaryUser->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        Payment::create([
            'user_id' => $februaryUser->id,
            'user_request_id' => $februaryRequest->id,
            'amount_minor' => 22222,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 2222,
            'trainer_net_minor' => 20000,
        ]);
        AppExpense::create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 5000,
            'notes' => 'February expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        Carbon::setTestNow();

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('فلترة مخصصة مفعلة')
            ->assertSee('111.11')
            ->assertSee('10.00')
            ->assertSee('101.11')
            ->assertDontSee('333.33');
    }
}
