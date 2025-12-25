<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\UserRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ReportsService
{
    public function recentPayments(?Carbon\CarbonImmutable $from = null, ?Carbon\CarbonImmutable $to = null, int $limit = 50): Collection
    {
        return Payment::query()
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->latest()->limit($limit)->get();
    }

    public function sales(?Carbon\CarbonImmutable $from = null, ?Carbon\CarbonImmutable $to = null): array
    {
        $query = Payment::where('status', 'succeeded')
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]));

        $totalMinor = (int) $query->clone()
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount_minor + COALESCE(app_fee_minor, 0) ELSE amount_minor END) as total',
                [Payment::TYPE_PLAN_FULL]
            )
            ->value('total');

        return [
            'payments' => $query->latest()->paginate(25),
            'totalMinor' => $totalMinor,
        ];
    }

    public function paymentsList(?string $type = null, ?string $status = null): LengthAwarePaginator
    {
        $q = Payment::query()->latest();
        if ($type) $q->where('type', $type);
        if ($status) $q->where('status', $status);
        return $q->paginate(25);
    }

    public function subscriptionsList(?string $status = null): LengthAwarePaginator
    {
        $q = UserRequest::with('plan','user')->latest();
        if ($status) $q->where('status', $status);
        return $q->paginate(25);
    }

    public function planSales(?Carbon\CarbonImmutable $from = null, ?Carbon\CarbonImmutable $to = null): array
    {
        $q = Payment::with(['user', 'userRequest.trainer', 'userRequest.plan'])
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]))
            ->latest();

        $totalQuery = clone $q;
        return [
            'payments' => $q->paginate(25),
            'totalMinor' => (int) $totalQuery->sum('amount_minor'),
        ];
    }

    public function appFees(?Carbon\CarbonImmutable $from = null, ?Carbon\CarbonImmutable $to = null): array
    {
        $q = Payment::with('user')->where('status', Payment::STATUS_SUCCEEDED)
            ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]));

        $sumQuery = clone $q;
        return [
            'payments' => $q->latest()->paginate(25),
            'totalMinor' => (int) $sumQuery->sum('app_fee_minor'),
        ];
    }

    public function vatReport(?Carbon\CarbonImmutable $from = null, ?Carbon\CarbonImmutable $to = null): array
    {
        $vatPercent = (float) config('app.vat_percent', 0.0);
        $q = Payment::with('user')->where('status', Payment::STATUS_SUCCEEDED)
            ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]));

        $payments = $q->latest()->paginate(25);
        $vatQuery = clone $q;
        $vatTotalMinor = (int) $vatQuery->get()->sum(function ($p) use ($vatPercent) {
            return (int) round($p->amount_minor * ($vatPercent / 100));
        });

        return [
            'payments' => $payments,
            'vatPercent' => $vatPercent,
            'vatTotalMinor' => $vatTotalMinor,
        ];
    }
}
