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

    public function sales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?string $paymentType = null): array
    {
        $query = $this->salesQuery($from, $to, [
            'type' => $paymentType,
        ]);

        $totalMinor = (int) (clone $query)->sum('amount_minor');

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
        ];
    }

    public function salesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?string $paymentType = null): Collection
    {
        return $this->salesQuery($from, $to, [
            'type' => $paymentType,
        ])->latest()->get();
    }

    public function salesReport(array $filters = []): array
    {
        $query = $this->salesQuery(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters
        );

        $totalMinor = (int) (clone $query)->sum('amount_minor');
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
            'count' => $count,
            'averageMinor' => $count > 0 ? (int) round($totalMinor / $count) : 0,
        ];
    }

    public function salesReportCollection(array $filters = []): Collection
    {
        return $this->salesQuery(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters
        )->latest()->get();
    }

    public function paymentsList(array|string|null $type = null, ?string $status = null): LengthAwarePaginator
    {
        return $this->paymentsQuery($this->normalizePaymentFilters($type, $status))->latest()->paginate(25);
    }

    public function paymentsCollection(array|string|null $type = null, ?string $status = null): Collection
    {
        return $this->paymentsQuery($this->normalizePaymentFilters($type, $status))->latest()->get();
    }

    public function paymentsReport(array $filters = []): array
    {
        $query = $this->paymentsQuery($filters);
        $totalMinor = (int) (clone $query)->sum('amount_minor');
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
            'count' => $count,
        ];
    }

    public function subscriptionsList(array|string|null $status = null): LengthAwarePaginator
    {
        return $this->subscriptionsQuery($this->normalizeSubscriptionFilters($status))->latest()->paginate(25);
    }

    public function subscriptionsCollection(array|string|null $status = null): Collection
    {
        return $this->subscriptionsQuery($this->normalizeSubscriptionFilters($status))->latest()->get();
    }

    public function subscriptionsReport(array $filters = []): array
    {
        $query = $this->subscriptionsQuery($filters);
        $count = (int) (clone $query)->count();

        return [
            'subscriptions' => (clone $query)->latest()->paginate(25),
            'count' => $count,
        ];
    }

    public function planSales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): array
    {
        $query = $this->planSalesQuery($from, $to, $filters);
        $totalMinor = (int) (clone $query)->sum('amount_minor');
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
            'count' => $count,
            'averageMinor' => $count > 0 ? (int) round($totalMinor / $count) : 0,
        ];
    }

    public function planSalesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Collection
    {
        return $this->planSalesQuery($from, $to, $filters)->latest()->get();
    }

    public function appFees(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): array
    {
        $query = $this->appFeesQuery($from, $to, $filters);
        $totalMinor = (int) (clone $query)->sum('app_fee_minor');
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
            'count' => $count,
            'averageMinor' => $count > 0 ? (int) round($totalMinor / $count) : 0,
        ];
    }

    public function appFeesCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Collection
    {
        return $this->appFeesQuery($from, $to, $filters)->latest()->get();
    }

    public function vatReport(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): array
    {
        $vatPercent = (float) config('app.vat_percent', 0.0);
        $query = $this->vatQuery($from, $to, $filters);

        $vatRate = $vatPercent / 100;
        $vatTotalMinor = $vatRate > 0
            ? (int) (clone $query)
                ->selectRaw('COALESCE(SUM(ROUND(amount_minor * ?, 0)), 0) as total', [$vatRate])
                ->value('total')
            : 0;
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'vatPercent' => $vatPercent,
            'vatTotalMinor' => $vatTotalMinor,
            'count' => $count,
        ];
    }

    public function vatCollection(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Collection
    {
        return $this->vatQuery($from, $to, $filters)->latest()->get();
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

    private function salesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Builder
    {
        return $this->applyPaymentFilters(
            $this->succeededPaymentsWithinRange($from, $to),
            $filters
        )
            ->with([
                'user',
                'userRequest.trainer',
                'userRequest.plan',
                'userRequest.country',
                'userRequest.plan.country',
            ]);
    }

    private function paymentsQuery(array $filters = []): Builder
    {
        return $this->applyPaymentFilters(
            Payment::query(),
            $filters
        )
            ->with('user')
            ->with([
                'userRequest.trainer',
                'userRequest.plan',
                'userRequest.country',
                'userRequest.plan.country',
            ]);
    }

    private function subscriptionsQuery(array $filters = []): Builder
    {
        return UserRequest::query()
            ->with(['plan', 'plan.country', 'country', 'user', 'trainer'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $requestStatus) => $query->where('status', $requestStatus))
            ->when($filters['plan_id'] ?? null, fn (Builder $query, string $planId) => $query->where('plan_id', $planId))
            ->when($filters['country_id'] ?? null, function (Builder $query, string $countryId): void {
                $query->where(function (Builder $nestedQuery) use ($countryId): void {
                    $nestedQuery->where('country_id', $countryId)
                        ->orWhereHas('plan', fn (Builder $planQuery) => $planQuery->where('country_id', $countryId));
                });
            })
            ->when($filters['from'] ?? null, fn (Builder $query, CarbonImmutable $from) => $query->whereDate('start_date', '>=', $from->toDateString()))
            ->when($filters['to'] ?? null, fn (Builder $query, CarbonImmutable $to) => $query->whereDate('start_date', '<=', $to->toDateString()))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%' . $search . '%';

                $query->where(function (Builder $nestedQuery) use ($like): void {
                    $nestedQuery->where('id', 'like', $like)
                        ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('trainer', function (Builder $trainerQuery) use ($like): void {
                            $trainerQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like));
                });
            });
    }

    private function planSalesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Builder
    {
        return $this->applyPaymentFilters(
            $this->succeededPaymentsWithinRange($from, $to)
                ->where('type', Payment::TYPE_PLAN_FULL),
            $filters
        )
            ->with([
                'user',
                'userRequest.trainer',
                'userRequest.plan',
                'userRequest.country',
                'userRequest.plan.country',
            ]);
    }

    private function appFeesQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Builder
    {
        return $this->applyPaymentFilters(
            $this->succeededPaymentsWithinRange($from, $to)
                ->where('type', Payment::TYPE_PLAN_FULL)
                ->where('app_fee_minor', '>', 0),
            $filters
        )->with([
            'user',
            'userRequest.trainer',
            'userRequest.plan',
            'userRequest.country',
            'userRequest.plan.country',
        ]);
    }

    private function vatQuery(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, array $filters = []): Builder
    {
        return $this->applyPaymentFilters(
            $this->succeededPaymentsWithinRange($from, $to),
            $filters
        )->with([
            'user',
            'userRequest.trainer',
            'userRequest.plan',
            'userRequest.country',
            'userRequest.plan.country',
        ]);
    }

    private function applyPaymentFilters(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $builder, string $type) => $builder->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $builder, string $method) => $builder->where('payment_method', $method))
            ->when($filters['country_id'] ?? null, function (Builder $builder, string $countryId): void {
                $builder->where(function (Builder $nestedQuery) use ($countryId): void {
                    $nestedQuery->whereHas('userRequest', fn (Builder $requestQuery) => $requestQuery->where('country_id', $countryId))
                        ->orWhereHas('userRequest.plan', fn (Builder $planQuery) => $planQuery->where('country_id', $countryId));
                });
            })
            ->when($filters['plan_id'] ?? null, function (Builder $builder, string $planId): void {
                $builder->whereHas('userRequest', fn (Builder $requestQuery) => $requestQuery->where('plan_id', $planId));
            })
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%' . $search . '%';

                $builder->where(function (Builder $nestedQuery) use ($like): void {
                    $nestedQuery->where('id', 'like', $like)
                        ->orWhere('user_request_id', 'like', $like)
                        ->orWhere('payment_method', 'like', $like)
                        ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('userRequest.trainer', function (Builder $trainerQuery) use ($like): void {
                            $trainerQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('userRequest.plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like));
                });
            });
    }

    private function normalizePaymentFilters(array|string|null $type, ?string $status = null): array
    {
        if (is_array($type)) {
            return $type;
        }

        return [
            'type' => $type,
            'status' => $status,
        ];
    }

    private function normalizeSubscriptionFilters(array|string|null $status): array
    {
        if (is_array($status)) {
            return $status;
        }

        return [
            'status' => $status,
        ];
    }
}
