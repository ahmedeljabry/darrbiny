<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Support\ReportCurrencyConverter;
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

    public function test_adding_country_exchange_rate_converts_jordanian_dinar_across_admin_bookings_and_payments(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.geo.countries.store'), [
                'name' => 'Jordan',
                'iso2' => 'JO',
                'currency' => 'JOD',
                'exchange_rate_to_sar' => '5.29',
            ])
            ->assertRedirect(route('admin.geo.index'));

        $storedRates = json_decode((string) Setting::query()
            ->where('key', ReportCurrencyConverter::SETTINGS_KEY)
            ->value('value'), true);

        $this->assertIsArray($storedRates);
        $this->assertSame(5.29, (float) ($storedRates['JOD'] ?? 0));

        $country = Country::query()->where('iso2', 'JO')->firstOrFail();
        $plan = Plan::create([
            'title' => 'Jordan Finance Plan',
            'description' => 'Plan for Jordanian finance conversion test',
            'price_min' => 150,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Jordan Finance User',
            'phone_with_cc' => '+962790000111',
            'currency' => 'JOD',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Amman Governorate',
            'area_level_2' => 'Amman',
            'area_level_3' => 'Abdali',
            'locality' => 'Shmeisani',
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PAID,
            'currency' => 'JOD',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 18_903,
            'currency' => 'JOD',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_890,
            'trainer_net_minor' => 17_013,
        ]);

        $convertedAmount = app(ReportCurrencyConverter::class)->formatConvertedMinor(18_903, 'JOD');

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');

        $this->actingAs($admin)
            ->get(route('admin.bookings.show', $request->id))
            ->assertOk()
            ->assertSee('العملة:</strong> SAR', false)
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');
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
