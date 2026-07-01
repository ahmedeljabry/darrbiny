<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
