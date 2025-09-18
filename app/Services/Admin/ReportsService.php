<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\UserRequest;
use Carbon\CarbonImmutable;
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

        return [
            'payments' => $query->latest()->paginate(25),
            'totalMinor' => (int) $query->clone()->sum('amount_minor'),
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
}

