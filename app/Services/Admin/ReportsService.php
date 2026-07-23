<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\UserRequest;
use App\Support\ReportCurrencyConverter;
use App\Support\Vat;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReportsService
{
    public function __construct(
        private readonly ReportCurrencyConverter $reportCurrencyConverter
    ) {}

    public function recentPayments(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, int $limit = 50): Collection
    {
        return $this->paymentsWithinRange($from, $to)
            ->with(['user', 'userRequest.country', 'userRequest.plan.country'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function sales(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?string $paymentType = null): array
    {
        $query = $this->salesQuery($from, $to, [
            'type' => $paymentType,
        ]);

        $grossTotalMinor = $this->sumGrossPaymentsToReportCurrency($query);
        $refundsMinor = $this->allocatedCancellationRefundsMinor($from, $to, ['type' => $paymentType], 'gross');
        $totalMinor = $grossTotalMinor - $refundsMinor;

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

        $grossTotalMinor = $this->sumGrossPaymentsToReportCurrency($query);
        $refundsMinor = $this->allocatedCancellationRefundsMinor(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters,
            'gross'
        );
        $totalMinor = $grossTotalMinor - $refundsMinor;
        $appFeeTotalMinor = $this->reportCurrencyConverter->convertGroupedMinorSumsToReportCurrency($query, 'app_fee_minor');
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'totalMinor' => $totalMinor,
            'appFeeTotalMinor' => $appFeeTotalMinor,
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
        $totalMinor = $this->sumGrossPaymentsToReportCurrency($query);
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
        $grossTotalMinor = $this->sumGrossPaymentsToReportCurrency($query);
        $refundsMinor = $this->allocatedCancellationRefundsMinor($from, $to, $filters, 'plan_full');
        $totalMinor = $grossTotalMinor - $refundsMinor;
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
        $grossTotalMinor = $this->reportCurrencyConverter->convertGroupedMinorSumsToReportCurrency($query, 'app_fee_minor');
        $refundsMinor = $this->allocatedCancellationRefundsMinor($from, $to, $filters, 'app_fee');
        $totalMinor = $grossTotalMinor - $refundsMinor;
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
        $query = $this->vatQuery($from, $to, $filters);
        $vatTotalMinor = (clone $query)
            ->get()
            ->sum(fn (Payment $payment) => $this->reportCurrencyConverter->convertMinor(
                Vat::minorForPayment($payment),
                (string) $payment->currency
            ));
        $count = (int) (clone $query)->count();

        return [
            'payments' => (clone $query)->latest()->paginate(25),
            'vatPercentLabel' => $this->vatPercentLabel($filters['country_id'] ?? null),
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
            ->with([
                'plan',
                'plan.country',
                'country',
                'user',
                'trainer',
                'payments' => fn ($query) => $query
                    ->where('status', Payment::STATUS_SUCCEEDED)
                    ->latest(),
            ])
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
                $like = '%'.$search.'%';
                $orderNumber = UserRequest::normalizeOrderNumberSearch($search);

                $query->where(function (Builder $nestedQuery) use ($like, $orderNumber): void {
                    $nestedQuery->where('id', 'like', $like)
                        ->orWhereRaw('CAST(order_number as CHAR) like ?', [$like])
                        ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('trainer', function (Builder $trainerQuery) use ($like): void {
                            $trainerQuery->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like));

                    if ($orderNumber !== null) {
                        $nestedQuery->orWhere('order_number', $orderNumber);
                    }
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

    private function vatPercentLabel(?string $countryId): string
    {
        if ($countryId) {
            $country = Country::query()->find($countryId);

            if ($country) {
                return number_format(Vat::percentForCountry($country), 2).'%';
            }
        }

        return 'حسب الدولة';
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
            ->when($filters['request_status'] ?? null, function (Builder $builder, string $requestStatus): void {
                $builder->whereHas('userRequest', fn (Builder $requestQuery) => $requestQuery->where('status', $requestStatus));
            })
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.$search.'%';
                $orderNumber = UserRequest::normalizeOrderNumberSearch($search);

                $builder->where(function (Builder $nestedQuery) use ($like, $orderNumber): void {
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
                        ->orWhereHas('userRequest.plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like))
                        ->orWhereHas('userRequest', function (Builder $requestQuery) use ($like, $orderNumber): void {
                            $requestQuery->whereRaw('CAST(order_number as CHAR) like ?', [$like]);

                            if ($orderNumber !== null) {
                                $requestQuery->orWhere('order_number', $orderNumber);
                            }
                        });
                });
            });
    }

    private function sumGrossPaymentsToReportCurrency(Builder $query): int
    {
        return (clone $query)
            ->with(['userRequest.country', 'userRequest.plan.country'])
            ->get()
            ->sum(fn (Payment $payment) => $this->reportCurrencyConverter->convertMinor(
                $payment->grossAmountMinor(),
                (string) $payment->currency
            ));
    }

    public function allocatedCancellationRefundsMinor(
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        array $filters = [],
        string $amountMode = 'gross'
    ): int {
        $cancellations = CancellationRequest::query()
            ->with([
                'userRequest.user',
                'userRequest.trainer',
                'userRequest.plan',
                'userRequest.country',
                'userRequest.payments',
                'userRequest.payments.user',
            ])
            ->where('status', CancellationRequest::STATUS_APPROVED)
            ->where('refund_amount_minor', '>', 0)
            ->when($from, fn (Builder $query, \DateTimeInterface $date) => $query->where('processed_at', '>=', $date))
            ->when($to, fn (Builder $query, \DateTimeInterface $date) => $query->where('processed_at', '<=', $date))
            ->get();

        return $cancellations->sum(function (CancellationRequest $cancellation) use ($filters, $amountMode): int {
            $request = $cancellation->userRequest;

            if (! $request || ! $this->cancellationMatchesFilters($cancellation, $filters)) {
                return 0;
            }

            $successfulPayments = $request->payments
                ->filter(fn (Payment $payment) => $payment->status === Payment::STATUS_SUCCEEDED)
                ->values();

            $totalSuccessfulMinor = (int) $successfulPayments
                ->sum(fn (Payment $payment) => $this->grossPaymentMinor($payment));
            if ($totalSuccessfulMinor <= 0) {
                return 0;
            }

            $relevantPayments = $successfulPayments
                ->filter(fn (Payment $payment) => $this->paymentMatchesFilters($payment, $request, $filters))
                ->values();

            $matchingMinor = match ($amountMode) {
                'plan_full' => (int) $relevantPayments
                    ->filter(fn (Payment $payment) => $payment->type === Payment::TYPE_PLAN_FULL)
                    ->sum(fn (Payment $payment) => $this->grossPaymentMinor($payment)),
                'plan_partial' => (int) $relevantPayments
                    ->filter(fn (Payment $payment) => $payment->type === Payment::TYPE_PLAN_PARTIAL)
                    ->sum(fn (Payment $payment) => $this->grossPaymentMinor($payment)),
                'booking_fees' => (int) $relevantPayments
                    ->filter(fn (Payment $payment) => in_array($payment->type, Payment::partialTypes(), true))
                    ->sum(fn (Payment $payment) => $this->grossPaymentMinor($payment)),
                'app_fee' => (int) $relevantPayments
                    ->filter(fn (Payment $payment) => $payment->type === Payment::TYPE_PLAN_FULL)
                    ->sum('app_fee_minor'),
                default => (int) $relevantPayments->sum(fn (Payment $payment) => $this->grossPaymentMinor($payment)),
            };

            if ($matchingMinor <= 0) {
                return 0;
            }

            $allocatedMinor = min(
                $matchingMinor,
                (int) round(((int) $cancellation->refund_amount_minor) * ($matchingMinor / $totalSuccessfulMinor))
            );

            return $this->reportCurrencyConverter->convertMinor(
                $allocatedMinor,
                $this->currencyForCancellationRefund($relevantPayments, $successfulPayments, $request)
            );
        });
    }

    private function grossPaymentMinor(Payment $payment): int
    {
        return $payment->grossAmountMinor();
    }

    private function currencyForCancellationRefund(Collection $relevantPayments, Collection $successfulPayments, ?UserRequest $request): string
    {
        $currency = $relevantPayments
            ->pluck('currency')
            ->merge($successfulPayments->pluck('currency'))
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->first(fn (string $value) => $value !== '');

        if ($currency !== null) {
            return $currency;
        }

        $requestCurrency = strtoupper(trim((string) ($request?->currency ?? '')));

        return $requestCurrency !== '' ? $requestCurrency : ReportCurrencyConverter::REPORT_CURRENCY;
    }

    private function cancellationMatchesFilters(CancellationRequest $cancellation, array $filters = []): bool
    {
        $request = $cancellation->userRequest;
        if (! $request) {
            return false;
        }

        if (($filters['country_id'] ?? null) !== null) {
            $countryId = (string) $filters['country_id'];
            $requestCountryId = (string) ($request->country_id ?? $request->plan?->country_id ?? '');

            if ($requestCountryId !== $countryId) {
                return false;
            }
        }

        if (($filters['plan_id'] ?? null) !== null && (string) $request->plan_id !== (string) $filters['plan_id']) {
            return false;
        }

        if (($filters['request_status'] ?? null) !== null && $request->status !== (string) $filters['request_status']) {
            return false;
        }

        if (($filters['search'] ?? null) !== null) {
            $needle = mb_strtolower((string) $filters['search']);
            $paymentHaystacks = $request->payments
                ->map(fn (Payment $payment) => [
                    (string) $payment->id,
                    (string) $payment->payment_method,
                ])
                ->flatten()
                ->all();
            $haystacks = [
                (string) $cancellation->id,
                (string) $request->id,
                (string) $request->order_number,
                (string) $request->formatted_order_number,
                (string) $request->user?->name,
                (string) $request->user?->phone_with_cc,
                (string) $request->trainer?->name,
                (string) $request->trainer?->phone_with_cc,
                (string) $request->plan?->title,
                ...$paymentHaystacks,
            ];

            $matchesSearch = collect($haystacks)
                ->filter()
                ->contains(fn (string $value) => str_contains(mb_strtolower($value), $needle));

            if (! $matchesSearch) {
                return false;
            }
        }

        return true;
    }

    private function paymentMatchesFilters(Payment $payment, UserRequest $request, array $filters = []): bool
    {
        if (($filters['type'] ?? null) !== null && $payment->type !== (string) $filters['type']) {
            return false;
        }

        if (($filters['payment_method'] ?? null) !== null && $payment->payment_method !== (string) $filters['payment_method']) {
            return false;
        }

        if (($filters['country_id'] ?? null) !== null) {
            $countryId = (string) $filters['country_id'];
            $requestCountryId = (string) ($request->country_id ?? $request->plan?->country_id ?? '');

            if ($requestCountryId !== $countryId) {
                return false;
            }
        }

        if (($filters['plan_id'] ?? null) !== null && (string) $request->plan_id !== (string) $filters['plan_id']) {
            return false;
        }

        if (($filters['request_status'] ?? null) !== null && $request->status !== (string) $filters['request_status']) {
            return false;
        }

        if (($filters['search'] ?? null) !== null) {
            $needle = mb_strtolower((string) $filters['search']);
            $haystacks = [
                (string) $payment->id,
                (string) $payment->user_request_id,
                (string) $request->order_number,
                (string) $request->formatted_order_number,
                (string) $payment->payment_method,
                (string) $payment->user?->name,
                (string) $payment->user?->phone_with_cc,
                (string) $request->trainer?->name,
                (string) $request->trainer?->phone_with_cc,
                (string) $request->plan?->title,
            ];

            return collect($haystacks)
                ->filter()
                ->contains(fn (string $value) => str_contains(mb_strtolower($value), $needle));
        }

        return true;
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
