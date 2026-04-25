<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\AppExpensesExport;
use App\Models\AppExpense;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminAppExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_app_expense_and_see_profit_summary(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009501',
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

        $this->actingAs($admin)
            ->post(route('admin.app-expenses.store'), [
                'type' => AppExpense::TYPE_OPERATING_EXPENSE,
                'amount' => 60,
                'notes' => 'Office operations',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تمت إضافة مصروف التطبيق بنجاح');

        $this->assertDatabaseHas('app_expenses', [
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 6_000,
            'notes' => 'Office operations',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-expenses.index'))
            ->assertOk()
            ->assertSee('مصروفات التطبيق')
            ->assertSee('مصروفات تشغيل')
            ->assertSee('180.00')
            ->assertSee('60.00')
            ->assertSee('120.00');
    }

    public function test_admin_can_update_and_delete_app_expense(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009502',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        $expense = AppExpense::query()->create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 4_500,
            'notes' => 'Initial operating expense',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.app-expenses.update', $expense->id), [
                'type' => AppExpense::TYPE_OPERATING_EXPENSE,
                'amount' => 75,
                'notes' => 'Updated note',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم تحديث مصروف التطبيق بنجاح');

        $this->assertDatabaseHas('app_expenses', [
            'id' => $expense->id,
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 7_500,
            'notes' => 'Updated note',
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.app-expenses.destroy', $expense->id))
            ->assertRedirect()
            ->assertSessionHas('status', 'تم حذف مصروف التطبيق بنجاح');

        $this->assertDatabaseMissing('app_expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_admin_can_export_app_expenses_to_excel(): void
    {
        Excel::fake();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009504',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_payments');

        AppExpense::query()->create([
            'type' => AppExpense::TYPE_OPERATING_EXPENSE,
            'amount_minor' => 12_500,
            'notes' => 'Operating expense export',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.app-expenses.index', [
                'type' => AppExpense::TYPE_OPERATING_EXPENSE,
                'export' => 'excel',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'app-expenses-' . now()->format('Y-m-d') . '.xlsx',
            fn (AppExpensesExport $export) => true
        );
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
            'title' => 'App Expense Plan',
            'description' => 'Plan for app expense tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009503',
        ]);
        $user->assignRole('USER');

        $request = UserRequest::create([
            'user_id' => $user->id,
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
