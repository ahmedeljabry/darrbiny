<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
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
}
