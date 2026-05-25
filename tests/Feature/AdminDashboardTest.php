<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppExpense;
use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use App\Services\Admin\AppWalletAccountService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
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

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 12500,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
            'notes' => 'Dashboard withdrawal request',
        ]);

        $walletBalance = number_format(app(AppWalletAccountService::class)->summary()['net_minor'] / 100, 2);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('إجمالي الإيرادات')
            ->assertSee('رسوم الحجز')
            ->assertSee('رسوم الباقات')
            ->assertSee('المصروفات')
            ->assertSee('رصيد محفظة التطبيق')
            ->assertSee('مستحقات المدربين')
            ->assertSee('صافي الربح')
            ->assertSee('قيمة الباقة بالكامل')
            ->assertSee('قيد التدريب')
            ->assertSee('الكورسات الملغاة')
            ->assertSee('703.68')
            ->assertSee('358.01')
            ->assertSee('34.56')
            ->assertSee('80.00')
            ->assertSee('125.00')
            ->assertSee($walletBalance)
            ->assertSee('0.00')
            ->assertSee('312.57')
            ->assertSee('الرصيد الحقيقي الحالي بالريال ولا يتأثر بفلتر التاريخ');
    }

    public function test_dashboard_does_not_require_trainer_role_to_count_trainers(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009031',
            'email' => 'admin-dashboard-no-trainer-role@example.com',
        ]);
        $admin->assignRole('ADMIN');

        Role::where('name', 'TRAINER')->delete();

        User::factory()->create([
            'phone_with_cc' => '+10000009032',
            'user_type' => \App\Enums\UserType::CAPTAIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('المدربون الجدد')
            ->assertSee('1');
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

        $walletBalance = number_format(app(AppWalletAccountService::class)->summary()['net_minor'] / 100, 2);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('فلترة مخصصة مفعلة')
            ->assertSee('10.00')
            ->assertSee('111.11')
            ->assertSee('11.11')
            ->assertSee('1.11')
            ->assertSee($walletBalance)
            ->assertDontSee('333.33');
    }

    public function test_dashboard_financial_totals_are_converted_to_report_currency(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009021',
            'email' => 'admin-dashboard-currency@example.com',
        ]);
        $admin->assignRole('ADMIN');

        Setting::create([
            'key' => 'reports.exchange_rates_to_sar',
            'value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE),
        ]);

        $country = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);

        $plan = Plan::create([
            'title' => 'Dashboard Currency Plan',
            'description' => 'Plan used for converted dashboard totals',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009022',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'EGP',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10_000,
            'currency' => 'EGP',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('8.00 SAR')
            ->assertSee('0.80 SAR')
            ->assertSee('محول للريال');
    }

    public function test_dashboard_revenue_deducts_cancellation_refunds_and_keeps_app_wallet_balance_separate(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009031',
            'email' => 'admin-dashboard-cancellation@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Dashboard Cancellation Plan',
            'description' => 'Plan used for cancellation deduction on dashboard',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009032',
        ]);

        $request = UserRequest::create([
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
            'user_request_id' => $request->id,
            'amount_minor' => 2000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 2000,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 8000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 800,
            'trainer_net_minor' => 7200,
        ]);

        CancellationRequest::create([
            'user_request_id' => $request->id,
            'user_id' => $user->id,
            'reason' => 'Dashboard cancellation',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 5000,
            'processed_at' => now(),
        ]);

        AppExpense::create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 100,
            'notes' => 'Dashboard cancellation expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('50.00 SAR')
            ->assertSee('10.00 SAR')
            ->assertSee('4.00 SAR')
            ->assertSee('1.00 SAR')
            ->assertSee('99.00 SAR')
            ->assertSee('13.00 SAR');
    }
}
