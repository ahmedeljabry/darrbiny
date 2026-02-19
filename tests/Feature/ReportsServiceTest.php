<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
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

    public function test_sales_total_uses_charged_amount_without_double_counting_app_fees(): void
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

        $this->assertSame(12_000, $totalMinor);
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
        $this->assertSame(4_000, $totalMinor);
    }

    public function test_app_fees_and_vat_totals_use_only_succeeded_payments(): void
    {
        config()->set('app.vat_percent', 15.0);

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

        DB::table('payments')->insert(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'user_request_id' => (string) Str::uuid(),
            'amount_minor' => 1_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'provider' => 'wallet',
            'provider_ref' => 'ref_' . Str::random(16),
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 100,
            'trainer_net_minor' => 900,
            'version' => 1,
            'deleted_at' => null,
            'created_at' => $defaultTimestamp,
            'updated_at' => $defaultTimestamp,
        ], $attributes));
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
