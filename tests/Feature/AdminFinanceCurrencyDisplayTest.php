<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinanceCurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_payments_page_displays_amounts_in_riyal(): void
    {
        $admin = $this->createAdmin();
        [, $request] = $this->createEgyptianPaidRequest();

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('8.00 SAR')
            ->assertDontSee('100.00 EGP');
    }

    public function test_admin_bookings_pages_display_financial_amounts_in_riyal(): void
    {
        $admin = $this->createAdmin();
        [, $request] = $this->createEgyptianPaidRequest();

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('8.00 SAR')
            ->assertDontSee('100.00 EGP');

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $request->id))
            ->assertOk()
            ->assertSee('العملة:</strong> SAR', false)
            ->assertSee('8.00 SAR')
            ->assertDontSee('100.00 EGP');
    }

    public function test_admin_created_booking_defaults_to_riyal_when_user_currency_is_missing(): void
    {
        $admin = $this->createAdmin();
        [$country, $plan] = $this->createPlan('Saudi Arabia', 'SA', 'SAR');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000009703',
            'currency' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.store'), [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'start_date' => now()->addDay()->toDateString(),
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_requests', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency' => 'SAR',
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009700',
            'email' => 'admin-finance-currency@example.com',
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
    }

    /**
     * @return array{0: User, 1: UserRequest}
     */
    private function createEgyptianPaidRequest(): array
    {
        Setting::query()->updateOrCreate(
            ['key' => 'reports.exchange_rates_to_sar'],
            ['value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE)]
        );

        [, $plan] = $this->createPlan('Egypt', 'EG', 'EGP');

        $user = User::factory()->create([
            'name' => 'Finance Currency User',
            'phone_with_cc' => '+20000009701',
            'currency' => 'EGP',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PAID,
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

        return [$user, $request];
    }

    /**
     * @return array{0: Country, 1: Plan}
     */
    private function createPlan(string $countryName, string $iso2, string $currency): array
    {
        $country = Country::create([
            'name' => $countryName,
            'iso2' => $iso2,
            'currency' => $currency,
        ]);

        $plan = Plan::create([
            'title' => $countryName . ' Finance Plan',
            'description' => 'Plan for finance currency tests',
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
