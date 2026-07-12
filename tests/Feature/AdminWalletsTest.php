<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\GatewayWalletAccountExport;
use App\Models\Country;
use App\Models\GatewayWalletTransaction;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminWalletsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_pages_use_customer_wallets_label(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007000',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000007009',
            'points_balance' => 50,
        ]);
        $user->assignRole('USER');

        $transaction = WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 2500,
            'type' => WalletTransaction::TYPE_TOPUP_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.wallets.index'))
            ->assertOk()
            ->assertSee('محافظ العملاء')
            ->assertSee('إدارة أرصدة محافظ العملاء');

        $this->actingAs($admin)
            ->get(route('admin.wallet-transactions.index'))
            ->assertOk()
            ->assertSee('محافظ العملاء')
            ->assertSee('إدارة طلبات الإضافة والسحب لمحافظ العملاء')
            ->assertDontSee('طلبات المحافظ');

        $this->actingAs($admin)
            ->get(route('admin.wallet-transactions.show', $transaction->id))
            ->assertOk()
            ->assertSee('محافظ العملاء')
            ->assertDontSee('طلبات المحافظ');
    }

    public function test_admin_can_add_balance_from_wallets_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007001',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000007002',
            'points_balance' => 20,
        ]);
        $user->assignRole('USER');

        $this->actingAs($admin)
            ->post(route('admin.wallets.store'), [
                'user_id' => $user->id,
                'amount' => 15,
                'notes' => 'Administrative credit',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إضافة الرصيد إلى المحفظة. الرصيد الحالي: 35');

        $this->assertSame(35, (int) $user->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 1500,
            'type' => 'adjustment',
            'status' => 'approved',
        ]);
    }

    public function test_admin_wallet_course_credit_uses_completed_payout_amount(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007003',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000007004',
            'user_type' => 'captain',
            'points_balance' => 0,
        ]);
        $trainer->assignRole('TRAINER');

        $country = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);

        $plan = Plan::create([
            'title' => 'Wallet payout plan',
            'description' => 'Completed payout amount source',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+10000007007',
        ]);
        $student->assignRole('USER');

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_COMPLETED,
            'currency' => 'EGP',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 7000,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 7000,
            'currency' => 'EGP',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 700,
            'trainer_net_minor' => 6300,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.wallets.store'), [
                'user_id' => $trainer->id,
                'amount' => 999,
                'course_reference' => '#'.$booking->order_number,
                'notes' => 'كورسات',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إضافة صافي 63 إلى المحفظة بعد خصم رسوم التطبيق. الرصيد الحالي: 63');

        $this->assertSame(63, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 6300,
            'type' => 'adjustment',
            'status' => 'approved',
            'notes' => 'إضافة مستحقات كورس رقم '.$booking->order_number,
        ]);
    }

    public function test_admin_wallet_credit_can_apply_app_fee_without_course_reference(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '10',
        ]);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007005',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000007006',
            'user_type' => 'captain',
            'points_balance' => 20,
        ]);
        $trainer->assignRole('TRAINER');

        $this->actingAs($admin)
            ->post(route('admin.wallets.store'), [
                'user_id' => $trainer->id,
                'amount' => 100,
                'apply_app_fee' => '1',
                'notes' => 'Payout after fee',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إضافة صافي 90 إلى المحفظة بعد خصم رسوم التطبيق. الرصيد الحالي: 110');

        $this->assertSame(110, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 9000,
            'type' => 'adjustment',
            'status' => 'approved',
            'notes' => 'Payout after fee',
        ]);
    }

    public function test_admin_can_view_gateway_wallet_pages_with_excel_style_totals(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007100',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        [$student, $booking] = $this->createGatewayWalletBooking();

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => Payment::METHOD_TAP,
            'gateway_reference' => 'tap-ref-100',
            'gateway_status' => 'captured',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => Payment::METHOD_TABBY,
            'gateway_reference' => 'tabby-ref-100',
            'gateway_status' => 'closed',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.gateway-wallets.show', 'tap'))
            ->assertOk()
            ->assertSee('محفظة حساب تاب')
            ->assertSee('tap-ref-100')
            ->assertSee('وارد: قيمة الباقات')
            ->assertSee('100.00 SAR')
            ->assertSee('1.00 SAR')
            ->assertSee('0.15 SAR')
            ->assertSee('98.85 SAR')
            ->assertDontSee('tabby-ref-100');

        $this->actingAs($admin)
            ->get(route('admin.gateway-wallets.show', 'tabby'))
            ->assertOk()
            ->assertSee('محفظة حساب تابي')
            ->assertSee('tabby-ref-100')
            ->assertSee('8.49 SAR')
            ->assertSee('1.27 SAR')
            ->assertSee('90.24 SAR');

        $this->actingAs($admin)
            ->get(route('admin.gateway-wallets.show', 'tamara'))
            ->assertOk()
            ->assertSee('محفظة حساب تمارا');
    }

    public function test_admin_can_record_gateway_wallet_manual_movement(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007101',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $this->actingAs($admin)
            ->post(route('admin.gateway-wallets.transactions.store', 'tap'), [
                'direction' => GatewayWalletTransaction::DIRECTION_IN,
                'source' => GatewayWalletTransaction::SOURCE_BANK_DEPOSIT,
                'amount' => 50,
                'notes' => 'Bank settlement transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم تسجيل حركة محفظة البوابة بنجاح');

        $this->assertDatabaseHas('gateway_wallet_transactions', [
            'gateway' => Payment::METHOD_TAP,
            'direction' => GatewayWalletTransaction::DIRECTION_IN,
            'source' => GatewayWalletTransaction::SOURCE_BANK_DEPOSIT,
            'amount_minor' => 5_000,
            'notes' => 'Bank settlement transfer',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.gateway-wallets.show', 'tap'))
            ->assertOk()
            ->assertSee('Bank settlement transfer')
            ->assertSee('50.00 SAR');
    }

    public function test_admin_can_export_gateway_wallet_excel(): void
    {
        Excel::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007102',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $this->actingAs($admin)
            ->get(route('admin.gateway-wallets.show', [
                'gateway' => 'tamara',
                'export' => 'excel',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'gateway-wallet-tamara-'.now()->format('Y-m-d').'.xlsx',
            fn (GatewayWalletAccountExport $export) => true
        );
    }

    /**
     * @return array{0: User, 1: UserRequest}
     */
    private function createGatewayWalletBooking(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Gateway Wallet Plan',
            'description' => 'Plan for gateway wallet account tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000007103',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');

        $student = User::factory()->create([
            'phone_with_cc' => '+10000007104',
        ]);
        $student->assignRole('USER');

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

        return [$student, $booking];
    }
}
