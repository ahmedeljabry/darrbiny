<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanScheduleItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use App\Models\WalletTransaction;
use App\Support\ReportCurrencyConverter;
use Carbon\Carbon;
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

    public function test_subscriptions_report_shows_order_number_and_amount(): void
    {
        $admin = $this->createAdmin();
        [$country, $plan] = $this->createLocationPlan();

        $user = User::factory()->create([
            'name' => 'Subscriptions User',
            'phone_with_cc' => '+10000008002',
        ]);

        $trainer = User::factory()->create([
            'name' => 'Subscriptions Trainer',
            'phone_with_cc' => '+10000008003',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');

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

        $this->actingAs($admin)
            ->get(route('admin.reports.subscriptions'))
            ->assertOk()
            ->assertSee('رقم الطلب')
            ->assertSee('المبلغ')
            ->assertSee('#'.$request->fresh()->formatted_order_number)
            ->assertSee('70.00 SAR')
            ->assertSee($country->name);
    }

    public function test_sales_report_can_filter_by_payment_type(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $user = User::factory()->create([
            'name' => 'Filtered Sales User',
            'phone_with_cc' => '+10000008031',
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
            'amount_minor' => 2500,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 2500,
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

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.sales', ['type' => Payment::TYPE_PLAN_PARTIAL]))
            ->assertOk();

        $response
            ->assertSee('رسوم الحجز')
            ->assertSee('25.00 SAR')
            ->assertDontSee('80.00 SAR');
    }

    public function test_payments_report_shows_distinct_type_labels_for_reservation_variants(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $user = User::factory()->create([
            'name' => 'Payments Types User',
            'phone_with_cc' => '+10000008033',
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
            'amount_minor' => 1500,
            'currency' => 'SAR',
            'type' => Payment::TYPE_RESERVATION_FEE,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 1500,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 2500,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 2500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.payments'))
            ->assertOk()
            ->assertSee('رسوم الحجز الثابتة')
            ->assertSee('رسوم الحجز');
    }

    public function test_sales_report_shows_rows_in_riyal_and_deducts_approved_cancellation_refunds(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        Setting::create([
            'key' => 'reports.exchange_rates_to_sar',
            'value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE),
        ]);

        $user = User::factory()->create([
            'name' => 'Egypt Sales User',
            'phone_with_cc' => '+20000008032',
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
            'amount_minor' => 10000,
            'currency' => 'EGP',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1000,
            'trainer_net_minor' => 9000,
        ]);

        CancellationRequest::create([
            'user_request_id' => $request->id,
            'user_id' => $user->id,
            'reason' => 'Cancellation after payment',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 2500,
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee('6.00 SAR')
            ->assertSee('8.00 SAR')
            ->assertSee('0.80 SAR')
            ->assertDontSee('100.00 EGP')
            ->assertSee('كل مبالغ التقرير معروضة بالـ SAR');
    }

    public function test_payments_report_rows_are_displayed_in_riyal(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        Setting::create([
            'key' => 'reports.exchange_rates_to_sar',
            'value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE),
        ]);

        $user = User::factory()->create([
            'name' => 'Egypt Payments User',
            'phone_with_cc' => '+20000008034',
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
            'amount_minor' => 10000,
            'currency' => 'EGP',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1000,
            'trainer_net_minor' => 9000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.payments'))
            ->assertOk()
            ->assertSee('8.00 SAR')
            ->assertDontSee('100.00 EGP')
            ->assertSee('كل مبالغ هذا التقرير معروضة بالـ SAR');
    }

    public function test_reports_convert_jordanian_dinar_amounts_using_saved_exchange_rate_including_vat(): void
    {
        $admin = $this->createAdmin();
        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
            'vat_percent' => 15.0,
        ]);

        $plan = Plan::create([
            'title' => 'Jordan Reports Plan',
            'description' => 'Plan for Jordanian reports conversion test',
            'price_min' => 150,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.update'), [
                'report_exchange_rates' => [
                    ['currency' => 'JOD', 'rate' => '5.29'],
                ],
            ])
            ->assertRedirect(route('admin.settings.index'));

        $user = User::factory()->create([
            'name' => 'Jordan Reports User',
            'phone_with_cc' => '+962790000222',
        ]);

        $trainer = User::factory()->create([
            'name' => 'Jordan Reports Trainer',
            'phone_with_cc' => '+962790000223',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');

        $request = UserRequest::create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Amman Governorate',
            'area_level_2' => 'Amman',
            'area_level_3' => 'Abdali',
            'locality' => 'Shmeisani',
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
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
        $convertedVat = app(ReportCurrencyConverter::class)->formatConvertedMinor((int) round(18_903 * 0.15), 'JOD');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');

        $this->actingAs($admin)
            ->get(route('admin.reports.payments'))
            ->assertOk()
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');

        $this->actingAs($admin)
            ->get(route('admin.reports.subscriptions'))
            ->assertOk()
            ->assertSee($convertedAmount)
            ->assertDontSee('189.03 JOD');

        $this->actingAs($admin)
            ->get(route('admin.reports.vat'))
            ->assertOk()
            ->assertSee($convertedVat)
            ->assertDontSee('189.03 JOD');
    }

    public function test_booking_and_finance_payment_pages_convert_jordanian_dinar_to_riyal(): void
    {
        $admin = $this->createAdmin();

        Setting::create([
            'key' => ReportCurrencyConverter::SETTINGS_KEY,
            'value' => json_encode(['JOD' => 5.29], JSON_UNESCAPED_UNICODE),
        ]);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Jordan Admin Currency Plan',
            'description' => 'Plan for admin currency conversion tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Jordan Admin Currency User',
            'phone_with_cc' => '+962790000012',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'JOD',
            'total_paid_minor' => 18_903,
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
            'app_fee_minor' => 0,
            'trainer_net_minor' => 18_903,
        ]);

        foreach ([
            route('admin.bookings.index'),
            route('admin.bookings.show', $request->id),
            route('admin.payments.index'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('999.97 SAR')
                ->assertDontSee('189.03 JOD');
        }
    }

    public function test_subscriptions_report_uses_payment_currency_when_request_currency_is_stale(): void
    {
        $admin = $this->createAdmin();

        Setting::create([
            'key' => ReportCurrencyConverter::SETTINGS_KEY,
            'value' => json_encode(['JOD' => 5.0], JSON_UNESCAPED_UNICODE),
        ]);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Jordan Subscription Currency Plan',
            'description' => 'Plan for stale subscription currency tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Jordan Subscription User',
            'phone_with_cc' => '+962790000010',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'USD',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10_000,
            'currency' => 'JOD',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.subscriptions'))
            ->assertOk()
            ->assertSee('500.00 SAR')
            ->assertDontSee('100.00 SAR');
    }

    public function test_sales_refunds_use_payment_currency_when_request_currency_is_stale(): void
    {
        $admin = $this->createAdmin();

        Setting::create([
            'key' => ReportCurrencyConverter::SETTINGS_KEY,
            'value' => json_encode(['JOD' => 5.0], JSON_UNESCAPED_UNICODE),
        ]);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Jordan Refund Currency Plan',
            'description' => 'Plan for stale refund currency tests',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Jordan Refund User',
            'phone_with_cc' => '+962790000011',
        ]);

        $request = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_CANCELLED,
            'currency' => 'USD',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10_000,
            'currency' => 'JOD',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        CancellationRequest::create([
            'user_request_id' => $request->id,
            'user_id' => $user->id,
            'reason' => 'Jordan refund',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 2_000,
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.sales'))
            ->assertOk()
            ->assertSee('400.00 SAR')
            ->assertSee('500.00 SAR')
            ->assertDontSee('480.00 SAR');
    }

    public function test_completed_payouts_report_filters_by_name_phone_and_date_range_and_hides_identifier_column(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'name' => 'Payout Student',
            'phone_with_cc' => '+10000008011',
        ]);
        $student->assignRole('USER');

        $matchingTrainer = User::factory()->create([
            'name' => 'Matching Trainer',
            'phone_with_cc' => '+10000008012',
            'user_type' => 'captain',
            'bank_name' => 'Alpha Bank',
            'bank_account' => '123456789',
            'iban' => 'SA0300000000001234567891',
        ]);
        $matchingTrainer->assignRole('TRAINER');

        $otherTrainer = User::factory()->create([
            'name' => 'Other Trainer',
            'phone_with_cc' => '+10000008013',
            'user_type' => 'captain',
            'bank_name' => 'Beta Bank',
            'bank_account' => '987654321',
            'iban' => 'SA0300000000001234567892',
        ]);
        $otherTrainer->assignRole('TRAINER');

        $matchingRequest = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $matchingTrainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-04-01',
            'status' => UserRequest::STATUS_COMPLETED,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $otherRequest = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $otherTrainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-04-04',
            'status' => UserRequest::STATUS_COMPLETED,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Carbon::setTestNow('2026-04-02 10:00:00');
        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $matchingRequest->id,
            'amount_minor' => 9000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 900,
            'trainer_net_minor' => 8100,
        ]);

        Carbon::setTestNow('2026-04-05 10:00:00');
        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $otherRequest->id,
            'amount_minor' => 11000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1100,
            'trainer_net_minor' => 9900,
        ]);
        Carbon::setTestNow();

        $this->actingAs($admin)
            ->get(route('admin.reports.completed-payouts', [
                'name' => 'Matching',
                'phone' => '08012',
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-03',
            ]))
            ->assertOk()
            ->assertSee('Matching Trainer')
            ->assertSee('+10000008012')
            ->assertSee('Alpha Bank')
            ->assertDontSee('Other Trainer')
            ->assertDontSee('>المعرف<', false);
    }

    public function test_completed_payouts_report_applies_withdrawal_to_latest_eligible_course(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $student = User::factory()->create([
            'name' => 'Payout Allocation Student',
            'phone_with_cc' => '+10000008051',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'name' => 'Payout Allocation Trainer',
            'phone_with_cc' => '+10000008052',
            'user_type' => 'captain',
            'bank_name' => 'Allocation Bank',
            'bank_account' => '54239800845',
            'iban' => 'SA5558986500',
        ]);
        $trainer->assignRole('TRAINER');

        $olderRequest = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-05-10',
            'status' => UserRequest::STATUS_COMPLETED,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $latestRequest = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-05-14',
            'status' => UserRequest::STATUS_COMPLETED,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Carbon::setTestNow('2026-05-10 11:43:00');
        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $olderRequest->id,
            'amount_minor' => 100_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 10_000,
            'trainer_net_minor' => 90_000,
        ]);

        Carbon::setTestNow('2026-05-14 08:13:00');
        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $latestRequest->id,
            'amount_minor' => 100_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 10_000,
            'trainer_net_minor' => 90_000,
        ]);

        WalletTransaction::create([
            'user_id' => $trainer->id,
            'amount' => 90_000,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_APPROVED,
            'processed_by' => $admin->id,
            'processed_at' => Carbon::parse('2026-05-14 12:19:00'),
        ]);
        Carbon::setTestNow();

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.completed-payouts'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<tr>.*?Payout Allocation Trainer.*?900\.00.*?900\.00.*?0\.00.*?منفذة.*?2026-05-14 08:13.*?<\/tr>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<tr>.*?Payout Allocation Trainer.*?900\.00.*?0\.00.*?900\.00.*?غير منفذة.*?2026-05-10 11:43.*?<\/tr>/s',
            $response->getContent()
        );
    }

    public function test_rejected_progress_report_filters_by_phone_and_date_range_and_hides_identifier_column(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $matchingStudent = User::factory()->create([
            'name' => 'Matching Student',
            'phone_with_cc' => '+10000008021',
        ]);
        $matchingStudent->assignRole('USER');

        $otherStudent = User::factory()->create([
            'name' => 'Other Student',
            'phone_with_cc' => '+10000008022',
        ]);
        $otherStudent->assignRole('USER');

        $trainer = User::factory()->create([
            'name' => 'Progress Trainer',
            'phone_with_cc' => '+10000008023',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');

        $matchingRequest = UserRequest::create([
            'user_id' => $matchingStudent->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-04-02',
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $otherRequest = UserRequest::create([
            'user_id' => $otherStudent->id,
            'trainer_id' => $trainer->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-04-05',
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $scheduleItem = PlanScheduleItem::create([
            'plan_id' => $plan->id,
            'day_number' => 1,
            'title' => 'Day 1',
            'position' => 1,
        ]);

        Carbon::setTestNow('2026-04-02 12:00:00');
        UserScheduleProgress::create([
            'user_request_id' => $matchingRequest->id,
            'plan_schedule_item_id' => $scheduleItem->id,
            'day_number' => 1,
            'status' => UserScheduleProgress::STATUS_REJECTED,
            'rejection_reason' => 'Need correction',
        ]);

        Carbon::setTestNow('2026-04-06 12:00:00');
        UserScheduleProgress::create([
            'user_request_id' => $otherRequest->id,
            'plan_schedule_item_id' => $scheduleItem->id,
            'day_number' => 1,
            'status' => UserScheduleProgress::STATUS_REJECTED,
            'rejection_reason' => 'Late submission',
        ]);
        Carbon::setTestNow();

        $this->actingAs($admin)
            ->get(route('admin.reports.rejected-progress', [
                'phone' => '08021',
                'date_from' => '2026-04-01',
                'date_to' => '2026-04-03',
            ]))
            ->assertOk()
            ->assertSee('Matching Student')
            ->assertSee('+10000008021')
            ->assertSee('Progress Trainer')
            ->assertDontSee('Other Student')
            ->assertDontSee('>المعرف<', false);
    }

    public function test_points_report_shows_referral_points_not_wallet_balance(): void
    {
        $admin = $this->createAdmin();
        [, $plan] = $this->createLocationPlan();

        $owner = User::factory()->create([
            'name' => 'Referral Owner',
            'phone_with_cc' => '+10000008041',
            'points_balance' => 777,
        ]);
        $owner->assignRole('USER');

        $referredUser = User::factory()->create([
            'name' => 'Referred User',
            'phone_with_cc' => '+10000008042',
            'referred_by' => $owner->id,
        ]);
        $referredUser->assignRole('USER');

        $request = UserRequest::create([
            'user_id' => $referredUser->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $referredUser->id,
            'user_request_id' => $request->id,
            'amount_minor' => 10000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1000,
            'trainer_net_minor' => 9000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.rewards.points'))
            ->assertOk()
            ->assertSee('نقاط الإحالة');

        $normalizedHtml = preg_replace('/\s+/u', ' ', $response->getContent());

        $this->assertIsString($normalizedHtml);
        $this->assertMatchesRegularExpression(
            '/Referral Owner.*?\+10000008041.*?(?:مستخدم|مدرب).*?<td>\s*1\s*<\/td>/u',
            $normalizedHtml
        );
        preg_match_all('/<tr>.*?<\/tr>/u', $normalizedHtml, $rowMatches);
        $ownerRow = collect($rowMatches[0] ?? [])
            ->first(fn (string $row): bool => str_contains($row, 'Referral Owner'));

        $this->assertIsString($ownerRow);
        preg_match_all('/<td>\s*(.*?)\s*<\/td>/u', $ownerRow, $cellMatches);

        $pointsCell = trim(strip_tags($cellMatches[1][4] ?? ''));
        $this->assertSame('1', $pointsCell);
        $this->assertNotSame('777', $pointsCell);
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
