<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\AppWalletAccountExport;
use App\Models\AppExpense;
use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminAppWalletAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_app_wallet_account_summary_and_entries(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009601',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        [$user, $request] = $this->createPaidRequestContext();

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_RESERVATION_FEE,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 10_000,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 5_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 5_000,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 20_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 3_000,
            'trainer_net_minor' => 17_000,
        ]);

        AppExpense::query()->create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 6_000,
            'notes' => 'Wallet account expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-wallet-account.index'))
            ->assertOk()
            ->assertSee('حساب محفظة التطبيق')
            ->assertSee('رسوم الحجز الثابتة')
            ->assertSee('رسوم الحجز على الباقات')
            ->assertSee('الدفع الكلي')
            ->assertSee('مصروفات تشغيل')
            ->assertSee('350.00')
            ->assertSee('60.00')
            ->assertSee('290.00');
    }

    public function test_admin_can_export_app_wallet_account_to_excel(): void
    {
        Excel::fake();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009602',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        [$user, $request] = $this->createPaidRequestContext();

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 8_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_RESERVATION_FEE,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 8_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-wallet-account.index', [
                'direction' => 'in',
                'export' => 'excel',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'app-wallet-account-' . now()->format('Y-m-d') . '.xlsx',
            fn (AppWalletAccountExport $export) => true
        );
    }

    public function test_app_wallet_summary_is_converted_to_riyal_while_entries_keep_original_currency(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009605',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        Setting::create([
            'key' => 'reports.exchange_rates_to_sar',
            'value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE),
        ]);

        [$user, $request] = $this->createPaidRequestContext();

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10_000,
            'currency' => 'EGP',
            'type' => Payment::TYPE_RESERVATION_FEE,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 10_000,
        ]);

        AppExpense::query()->create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 500,
            'notes' => 'Wallet converted expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-wallet-account.index'))
            ->assertOk()
            ->assertSee('8.00 SAR')
            ->assertSee('5.00 SAR')
            ->assertSee('3.00 SAR')
            ->assertSee('100.00 EGP');
    }

    public function test_admin_can_filter_app_wallet_by_app_fee_analysis(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009606',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        [$user, $request] = $this->createPaidRequestContext();

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 20_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 3_000,
            'trainer_net_minor' => 17_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-wallet-account.index', [
                'direction' => 'in',
                'source' => 'app_fee',
            ]))
            ->assertOk()
            ->assertSee('رسوم التطبيق على الدفع الكلي')
            ->assertSee('30.00')
            ->assertDontSee('200.00');
    }

    public function test_app_wallet_summary_and_entries_include_approved_cancellation_refunds(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009607',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        [$user, $request] = $this->createPaidRequestContext();

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 4_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 4_000,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 9_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 900,
            'trainer_net_minor' => 8_100,
        ]);

        CancellationRequest::create([
            'user_request_id' => $request->id,
            'user_id' => $user->id,
            'reason' => 'Refund after cancellation',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 5_000,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-wallet-account.index'))
            ->assertOk()
            ->assertSee('استرداد باقات')
            ->assertSee('130.00')
            ->assertSee('50.00')
            ->assertSee('80.00')
            ->assertSee('Refund after cancellation')
            ->assertSee('#' . $request->formatted_order_number);
    }

    /**
     * @return array{0: User, 1: UserRequest}
     */
    private function createPaidRequestContext(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'App Wallet Plan',
            'description' => 'Plan for app wallet account tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000009603',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009604',
        ]);
        $user->assignRole('USER');

        $request = UserRequest::create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        return [$user, $request];
    }
}
