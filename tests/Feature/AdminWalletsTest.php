<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalletsTest extends TestCase
{
    use RefreshDatabase;

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
            'amount' => 15,
            'type' => 'adjustment',
            'status' => 'approved',
        ]);
    }

    public function test_admin_credit_uses_standard_course_payout_note_when_course_reference_is_provided(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '10',
        ]);

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

        $this->actingAs($admin)
            ->post(route('admin.wallets.store'), [
                'user_id' => $trainer->id,
                'amount' => 100,
                'course_reference' => '#C-245',
                'notes' => 'كورسات',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم إضافة صافي 90 إلى المحفظة بعد خصم رسوم التطبيق. الرصيد الحالي: 90');

        $this->assertSame(90, (int) $trainer->fresh()->points_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $trainer->id,
            'amount' => 90,
            'type' => 'adjustment',
            'status' => 'approved',
            'notes' => 'إضافة مستحقات كورس رقم #C-245',
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
            'amount' => 90,
            'type' => 'adjustment',
            'status' => 'approved',
            'notes' => 'Payout after fee',
        ]);
    }
}
