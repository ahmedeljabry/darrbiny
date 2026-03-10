<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sales_report_shows_country_column_and_value(): void
    {
        $admin = $this->createAdmin();
        [$country, $plan] = $this->createLocationPlan();

        $user = User::factory()->create([
            'name' => 'Sales User',
            'phone_with_cc' => '+10000008001',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PAID,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1000,
            'trainer_net_minor' => 9000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee('الدولة')
            ->assertSee($country->name);
    }

    public function test_app_fees_report_includes_only_plan_full_payments_with_fees(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $user = User::factory()->create([
            'name' => 'Fees User',
            'phone_with_cc' => '+10000008002',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PAID,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 7000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 700,
            'trainer_net_minor' => 6300,
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

        $this->actingAs($admin)
            ->get(route('admin.reports.app-fees'))
            ->assertOk()
            ->assertSee(Payment::TYPE_PLAN_FULL)
            ->assertDontSee(Payment::TYPE_PLAN_PARTIAL);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000008000',
            'email' => 'admin-reports-data@example.com',
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
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
            'title' => 'Plan Reports',
            'description' => 'Plan for reports tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        return [$country, $plan];
    }
}
