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
        $query = $this->salesQuery($from, $to);

        $totalMinor = (int) (clone $query)->sum('amount_minor');

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
        ];
    }

    public function salesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return $this->salesQuery($from, $to)->latest()->get();
    }

    public function paymentsList(?string $type = null, ?string $status = null): LengthAwarePaginator
    {
        return $this->paymentsQuery($type, $status)->latest()->paginate(25);
    }

    public function paymentsCollection(?string $type = null, ?string $status = null): Collection
    {
        return $this->paymentsQuery($type, $status)->latest()->get();
    }

    public function subscriptionsList(?string $status = null): LengthAwarePaginator
    {
        return $this->subscriptionsQuery($status)->latest()->paginate(25);
    }

    public function subscriptionsCollection(?string $status = null): Collection
    {
        return $this->subscriptionsQuery($status)->latest()->get();
    }

    public function planSales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = $this->planSalesQuery($from, $to);

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => (int) (clone $query)->sum('amount_minor'),
        ];
    }

    public function planSalesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return $this->planSalesQuery($from, $to)->latest()->get();
    }

    public function appFees(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $query = $this->appFeesQuery($from, $to);

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => (int) (clone $query)->sum('app_fee_minor'),
        ];
    }

    public function appFeesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return $this->appFeesQuery($from, $to)->latest()->get();
    }

    public function vatReport(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $vatPercent = (float) config('app.vat_percent', 0.0);
        $query = $this->vatQuery($from, $to);

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

    public function vatCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        return $this->vatQuery($from, $to)->latest()->get();
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

    private function salesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $this->succeededPaymentsWithinRange($from, $to)->with('user');
    }

    private function paymentsQuery(?string $type = null, ?string $status = null): Builder
    {
        return Payment::query()
            ->with('user')
            ->when($type, fn (Builder $query, string $paymentType) => $query->where('type', $paymentType))
            ->when($status, fn (Builder $query, string $paymentStatus) => $query->where('status', $paymentStatus));
    }

    private function subscriptionsQuery(?string $status = null): Builder
    {
        return UserRequest::query()
            ->with(['plan', 'user'])
            ->when($status, fn (Builder $query, string $requestStatus) => $query->where('status', $requestStatus));
    }

    private function planSalesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $this->succeededPaymentsWithinRange($from, $to)
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->with(['user', 'userRequest.trainer', 'userRequest.plan']);
    }

    private function appFeesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $this->succeededPaymentsWithinRange($from, $to)->with('user');
    }

    private function vatQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $this->succeededPaymentsWithinRange($from, $to)->with('user');
    }
}
