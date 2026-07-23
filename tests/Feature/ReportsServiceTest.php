<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\UserRequest;
use App\Services\Admin\ReportsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?string $vatCountryId = null;

    public function test_sales_total_uses_vat_inclusive_charged_amount_without_double_counting_app_fees(): void
    {
        $this->createPayment([
            'type' => Payment::TYPE_PLAN_FULL,
            'amount_minor' => 10_000,
            'app_fee_minor' => 1_000,
            'status' => Payment::STATUS_SUCCEEDED,
        ]);
        $this->createPayment([
            'type' => 'reservation_fee',
            'amount_minor' => 2_000,
            'app_fee_minor' => 2_000,
            'status' => Payment::STATUS_SUCCEEDED,
        ]);
        $this->createPayment([
            'type' => Payment::TYPE_PLAN_FULL,
            'amount_minor' => 7_000,
            'app_fee_minor' => 700,
            'status' => Payment::STATUS_FAILED,
        ]);

        ['payments' => $payments, 'totalMinor' => $totalMinor] = app(ReportsService::class)->sales();

        $this->assertSame(13_800, $totalMinor);
        $this->assertSame(2, $payments->total());
    }

    public function test_sales_accepts_carbon_immutable_date_filters(): void
    {
        $this->createPayment([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 4_000,
            'app_fee_minor' => 400,
            'created_at' => CarbonImmutable::parse('2026-01-12 08:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-01-12 08:00:00'),
        ]);
        $this->createPayment([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 8_000,
            'app_fee_minor' => 800,
            'created_at' => CarbonImmutable::parse('2026-01-13 08:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-01-13 08:00:00'),
        ]);

        ['payments' => $payments, 'totalMinor' => $totalMinor] = app(ReportsService::class)->sales(
            CarbonImmutable::parse('2026-01-12')->startOfDay(),
            CarbonImmutable::parse('2026-01-12')->endOfDay(),
        );

        $this->assertSame(1, $payments->total());
        $this->assertSame(4_600, $totalMinor);
    }

    public function test_app_fees_and_vat_totals_use_only_succeeded_payments(): void
    {
        $this->createPayment([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10_000,
            'app_fee_minor' => 1_000,
        ]);
        $this->createPayment([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 2_000,
            'app_fee_minor' => 2_000,
        ]);
        $this->createPayment([
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 9_000,
            'app_fee_minor' => 900,
        ]);

        $service = app(ReportsService::class);

        ['totalMinor' => $appFeesMinor] = $service->appFees();
        ['vatTotalMinor' => $vatTotalMinor] = $service->vatReport();

        $this->assertSame(3_000, $appFeesMinor);
        $this->assertSame(1_800, $vatTotalMinor);
    }

    public function test_report_totals_are_converted_to_report_currency_when_rates_exist(): void
    {
        Setting::create([
            'key' => 'reports.exchange_rates_to_sar',
            'value' => json_encode(['EGP' => 0.08], JSON_UNESCAPED_UNICODE),
        ]);

        $this->createPayment([
            'currency' => 'SAR',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10_000,
            'app_fee_minor' => 1_000,
        ]);

        $this->createPayment([
            'currency' => 'EGP',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10_000,
            'app_fee_minor' => 1_000,
        ]);

        $service = app(ReportsService::class);

        ['totalMinor' => $salesMinor] = $service->sales();
        ['totalMinor' => $paymentsMinor] = $service->paymentsReport();
        ['totalMinor' => $appFeesMinor] = $service->appFees();
        ['vatTotalMinor' => $vatTotalMinor] = $service->vatReport();

        $this->assertSame(12_420, $salesMinor);
        $this->assertSame(12_420, $paymentsMinor);
        $this->assertSame(1_080, $appFeesMinor);
        $this->assertSame(1_620, $vatTotalMinor);
    }

    public function test_sales_collection_returns_all_filtered_rows_for_export(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->createPayment([
                'status' => Payment::STATUS_SUCCEEDED,
                'amount_minor' => 1_000 + $i,
            ]);
        }
        $this->createPayment([
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 9_999,
        ]);

        $service = app(ReportsService::class);

        ['payments' => $paginated] = $service->sales();
        $collection = $service->salesCollection();

        $this->assertSame(30, $paginated->total());
        $this->assertSame(25, $paginated->count());
        $this->assertCount(30, $collection);
    }

    public function test_sales_can_be_filtered_by_payment_type(): void
    {
        $this->createPayment([
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 2_500,
        ]);
        $this->createPayment([
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 8_500,
        ]);

        ['payments' => $payments, 'totalMinor' => $totalMinor] = app(ReportsService::class)->sales(
            paymentType: Payment::TYPE_PLAN_PARTIAL,
        );

        $this->assertSame(1, $payments->total());
        $this->assertSame(2_875, $totalMinor);
        $this->assertSame(Payment::TYPE_PLAN_PARTIAL, $payments->first()->type);
    }

    public function test_payments_and_subscriptions_collections_return_full_filtered_rows(): void
    {
        for ($i = 0; $i < 28; $i++) {
            $this->createPayment([
                'type' => Payment::TYPE_PLAN_FULL,
                'status' => Payment::STATUS_SUCCEEDED,
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createPayment([
                'type' => 'reservation_fee',
                'status' => Payment::STATUS_FAILED,
            ]);
        }

        for ($i = 0; $i < 27; $i++) {
            $this->createUserRequest([
                'status' => UserRequest::STATUS_IN_TRAINING,
            ]);
        }
        for ($i = 0; $i < 4; $i++) {
            $this->createUserRequest([
                'status' => UserRequest::STATUS_CANCELLED,
            ]);
        }

        $service = app(ReportsService::class);

        $paymentsPage = $service->paymentsList(Payment::TYPE_PLAN_FULL, Payment::STATUS_SUCCEEDED);
        $paymentsCollection = $service->paymentsCollection(Payment::TYPE_PLAN_FULL, Payment::STATUS_SUCCEEDED);

        $subscriptionsPage = $service->subscriptionsList(UserRequest::STATUS_IN_TRAINING);
        $subscriptionsCollection = $service->subscriptionsCollection(UserRequest::STATUS_IN_TRAINING);

        $this->assertSame(28, $paymentsPage->total());
        $this->assertSame(25, $paymentsPage->count());
        $this->assertCount(28, $paymentsCollection);

        $this->assertSame(27, $subscriptionsPage->total());
        $this->assertSame(25, $subscriptionsPage->count());
        $this->assertCount(27, $subscriptionsCollection);
    }

    private function createPayment(array $attributes = []): void
    {
        $defaultTimestamp = CarbonImmutable::parse('2026-01-10 10:00:00');
        $paymentId = (string) ($attributes['id'] ?? Str::uuid());
        $requestId = (string) ($attributes['user_request_id'] ?? Str::uuid());
        $countryId = $this->vatCountryId();

        if (! isset($attributes['user_request_id'])) {
            DB::table('user_requests')->insert([
                'id' => $requestId,
                'user_id' => (string) ($attributes['user_id'] ?? Str::uuid()),
                'trainer_id' => null,
                'plan_id' => (string) Str::uuid(),
                'country_id' => $countryId,
                'start_date' => '2026-01-15',
                'has_user_car' => false,
                'wants_trainer_car' => false,
                'needs_pickup' => false,
                'latitude' => null,
                'longitude' => null,
                'status' => UserRequest::STATUS_PAID,
                'currency' => (string) ($attributes['currency'] ?? 'SAR'),
                'app_fee_reserved_minor' => 0,
                'total_paid_minor' => 0,
                'version' => 1,
                'deleted_at' => null,
                'created_at' => $defaultTimestamp,
                'updated_at' => $defaultTimestamp,
            ]);
        }

        DB::table('payments')->insert(array_merge([
            'id' => $paymentId,
            'user_id' => (string) Str::uuid(),
            'user_request_id' => $requestId,
            'amount_minor' => 1_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 100,
            'trainer_net_minor' => 900,
            'version' => 1,
            'deleted_at' => null,
            'created_at' => $defaultTimestamp,
            'updated_at' => $defaultTimestamp,
        ], $attributes));
    }

    private function vatCountryId(): string
    {
        if ($this->vatCountryId !== null) {
            return $this->vatCountryId;
        }

        $this->vatCountryId = (string) Str::uuid();

        DB::table('countries')->insert([
            'id' => $this->vatCountryId,
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
            'reservation_fee_minor' => null,
            'vat_percent' => 15.0,
            'created_at' => CarbonImmutable::parse('2026-01-10 10:00:00'),
            'updated_at' => CarbonImmutable::parse('2026-01-10 10:00:00'),
        ]);

        return $this->vatCountryId;
    }

    private function createUserRequest(array $attributes = []): void
    {
        $defaultTimestamp = CarbonImmutable::parse('2026-01-10 10:00:00');

        DB::table('user_requests')->insert(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'trainer_id' => null,
            'plan_id' => (string) Str::uuid(),
            'start_date' => '2026-01-15',
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
            'latitude' => null,
            'longitude' => null,
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'version' => 1,
            'deleted_at' => null,
            'created_at' => $defaultTimestamp,
            'updated_at' => $defaultTimestamp,
        ], $attributes));
    }
}
