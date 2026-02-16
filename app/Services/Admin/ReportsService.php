<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\UserRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ReportsService
{
    public function recentPayments(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, int $limit = 50): Collection
    {
        return $this->paymentsWithinRange($from, $to)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function sales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = $this->succeededPaymentsWithinRange($from, $to)->with('user');

        $totalMinor = (int) (clone $query)->sum('amount_minor');

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
        ];
    }

    public function paymentsList(?string $type = null, ?string $status = null): LengthAwarePaginator
    {
        return Payment::query()
            ->with('user')
            ->when($type, fn (Builder $query, string $paymentType) => $query->where('type', $paymentType))
            ->when($status, fn (Builder $query, string $paymentStatus) => $query->where('status', $paymentStatus))
            ->latest()
            ->paginate(25);
    }

    public function subscriptionsList(?string $status = null): LengthAwarePaginator
    {
        return UserRequest::query()
            ->with(['plan', 'user'])
            ->when($status, fn (Builder $query, string $requestStatus) => $query->where('status', $requestStatus))
            ->latest()
            ->paginate(25);
    }

    public function planSales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = $this->succeededPaymentsWithinRange($from, $to)
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->with(['user', 'userRequest.trainer', 'userRequest.plan']);

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => (int) (clone $query)->sum('amount_minor'),
        ];
    }

    public function appFees(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = $this->succeededPaymentsWithinRange($from, $to)->with('user');

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => (int) (clone $query)->sum('app_fee_minor'),
        ];
    }

    public function vatReport(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $vatPercent = (float) config('app.vat_percent', 0.0);
        $query = $this->succeededPaymentsWithinRange($from, $to)->with('user');

        $vatRate = $vatPercent / 100;
        $vatTotalMinor = $vatRate > 0
            ? (int) (clone $query)
                ->selectRaw('COALESCE(SUM(ROUND(amount_minor * ?, 0)), 0) as total', [$vatRate])
                ->value('total')
            : 0;

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'vatPercent' => $vatPercent,
            'vatTotalMinor' => $vatTotalMinor,
        ];
    }

    private function paymentsWithinRange(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return Payment::query()
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to));
    }

    private function succeededPaymentsWithinRange(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $this->paymentsWithinRange($from, $to)->where('status', Payment::STATUS_SUCCEEDED);
    }
}
