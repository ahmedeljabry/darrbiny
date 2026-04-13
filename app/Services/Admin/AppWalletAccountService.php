<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AppExpense;
use App\Models\Payment;
use App\Support\ReportCurrencyConverter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AppWalletAccountService
{
    public function __construct(
        private readonly ReportCurrencyConverter $reportCurrencyConverter
    ) {}

    public function sourceOptions(): array
    {
        return [
            Payment::TYPE_RESERVATION_FEE => 'وارد: رسوم الحجز الثابتة',
            Payment::TYPE_PLAN_PARTIAL => 'وارد: رسوم الحجز على الباقات',
            Payment::TYPE_PLAN_FULL => 'وارد: الدفع الكلي',
            'app_fee' => 'وارد: رسوم التطبيق على الدفع الكلي',
            AppExpense::TYPE_OPERATING_EXPENSE => 'صادر: مصروفات تشغيل',
            AppExpense::TYPE_TRAINER_DUES => 'صادر: مستحقات مدربين',
            AppExpense::TYPE_PACKAGE_REFUND => 'صادر: استرداد باقات',
            AppExpense::TYPE_PROFIT_WITHDRAWAL => 'صادر: سحب أرباح',
        ];
    }

    /**
     * @return array{incoming_minor:int, outgoing_minor:int, net_minor:int}
     */
    public function summary(array $filters = []): array
    {
        $incomingMinor = 0;
        $outgoingMinor = 0;

        if (($filters['direction'] ?? null) !== 'out') {
            $incomingQuery = $this->incomingPaymentsQuery($filters);
            $source = $filters['source'] ?? null;

            if ($source === 'app_fee') {
                $incomingMinor = $this->reportCurrencyConverter->convertGroupedMinorSumsToReportCurrency($incomingQuery, 'app_fee_minor');
            } else {
                $incomingMinor = $this->reportCurrencyConverter->convertGroupedMinorSumsToReportCurrency($incomingQuery, 'amount_minor');
            }
        }

        if (($filters['direction'] ?? null) !== 'in') {
            $outgoingMinor = (int) $this->outgoingExpensesQuery($filters)->sum('amount_minor');
        }

        return [
            'incoming_minor' => $incomingMinor,
            'outgoing_minor' => $outgoingMinor,
            'net_minor' => $incomingMinor - $outgoingMinor,
        ];
    }

    public function ledgerEntries(array $filters = []): Collection
    {
        return $this->incomingEntries($filters)
            ->concat($this->outgoingEntries($filters))
            ->sortByDesc(fn (object $entry) => $entry->occurred_at?->getTimestamp() ?? 0)
            ->values();
    }

    private function incomingEntries(array $filters): Collection
    {
        if (($filters['direction'] ?? null) === 'out') {
            return collect();
        }

        $sourceFilter = $filters['source'] ?? null;

        return $this->incomingPaymentsQuery($filters)
            ->get()
            ->map(function (Payment $payment) use ($sourceFilter): object {
                $isAppFeeEntry = $sourceFilter === 'app_fee' && $payment->type === Payment::TYPE_PLAN_FULL;
                $amountMinor = $isAppFeeEntry ? (int) $payment->app_fee_minor : (int) $payment->amount_minor;
                $sourceKey = $isAppFeeEntry ? 'app_fee' : (string) $payment->type;

                return (object) [
                    'reference_id' => $payment->id,
                    'reference_label' => '#' . substr((string) $payment->id, 0, 8),
                    'direction' => 'in',
                    'direction_label' => 'وارد',
                    'source_key' => $sourceKey,
                    'source_label' => $this->entrySourceLabel($sourceKey),
                    'description' => $this->incomingDescription($sourceKey),
                    'order_reference' => $payment->user_request_id ? '#' . substr((string) $payment->user_request_id, 0, 8) : '—',
                    'counterparty' => $payment->user?->name ?? 'غير معروف',
                    'details' => collect([
                        $payment->userRequest?->trainer?->name ? 'المدرب: ' . $payment->userRequest->trainer->name : null,
                        $payment->userRequest?->plan?->title ? 'الباقة: ' . $payment->userRequest->plan->title : null,
                    ])->filter()->implode(' | ') ?: '—',
                    'amount_minor' => $amountMinor,
                    'report_amount_minor' => $this->reportCurrencyConverter->convertMinor($amountMinor, $payment->currency),
                    'currency' => $payment->currency ?: 'SAR',
                    'report_currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => 'وسيلة الدفع: ' . strtoupper((string) ($payment->payment_method ?? '-')),
                    'occurred_at' => $payment->created_at,
                ];
            });
    }

    private function outgoingEntries(array $filters): Collection
    {
        if (($filters['direction'] ?? null) === 'in') {
            return collect();
        }

        return $this->outgoingExpensesQuery($filters)
            ->get()
            ->map(function (AppExpense $expense): object {
                return (object) [
                    'reference_id' => $expense->id,
                    'reference_label' => '#' . substr((string) $expense->id, 0, 8),
                    'direction' => 'out',
                    'direction_label' => 'صادر',
                    'source_key' => (string) $expense->type,
                    'source_label' => $this->entrySourceLabel((string) $expense->type),
                    'description' => 'تسجيل مصروف من محفظة التطبيق',
                    'order_reference' => '—',
                    'counterparty' => $expense->creator?->name ?? 'الإدارة',
                    'details' => $expense->updater?->name ? 'آخر تحديث بواسطة: ' . $expense->updater->name : '—',
                    'amount_minor' => (int) $expense->amount_minor,
                    'report_amount_minor' => $this->reportCurrencyConverter->convertMinor((int) $expense->amount_minor, 'SAR'),
                    'currency' => 'SAR',
                    'report_currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => $expense->notes ?: '—',
                    'occurred_at' => $expense->created_at,
                ];
            });
    }

    private function incomingPaymentsQuery(array $filters): Builder
    {
        $source = $filters['source'] ?? null;

        $query = Payment::query()
            ->with(['user', 'userRequest.trainer', 'userRequest.plan'])
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereIn('type', [
                Payment::TYPE_RESERVATION_FEE,
                Payment::TYPE_PLAN_PARTIAL,
                Payment::TYPE_PLAN_FULL,
            ]);

        if ($source === 'app_fee') {
            $query
                ->where('type', Payment::TYPE_PLAN_FULL)
                ->where('app_fee_minor', '>', 0);
        } elseif (in_array($source, [
            Payment::TYPE_RESERVATION_FEE,
            Payment::TYPE_PLAN_PARTIAL,
            Payment::TYPE_PLAN_FULL,
        ], true)) {
            $query->where('type', $source);
        } elseif ($source !== null && ! $this->isIncomingSource($source)) {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%' . $search . '%';

                $builder->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('id', 'like', $like)
                        ->orWhere('user_request_id', 'like', $like)
                        ->orWhere('payment_method', 'like', $like)
                        ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                            $userQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('userRequest.trainer', function (Builder $trainerQuery) use ($like): void {
                            $trainerQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('userRequest.plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like));
                });
            });
    }

    private function outgoingExpensesQuery(array $filters): Builder
    {
        $source = $filters['source'] ?? null;

        $query = AppExpense::query()->with(['creator', 'updater']);

        if ($source !== null) {
            if (array_key_exists($source, AppExpense::typeLabels())) {
                $query->where('type', $source);
            } elseif ($this->isIncomingSource($source)) {
                $query->whereRaw('1 = 0');
            }
        }

        return $query
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%' . $search . '%';

                $builder->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('id', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('creator', function (Builder $creatorQuery) use ($like): void {
                            $creatorQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('updater', function (Builder $updaterQuery) use ($like): void {
                            $updaterQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        });
                });
            });
    }

    private function entrySourceLabel(string $sourceKey): string
    {
        return $this->sourceOptions()[$sourceKey] ?? $sourceKey;
    }

    private function incomingDescription(string $sourceKey): string
    {
        return match ($sourceKey) {
            Payment::TYPE_RESERVATION_FEE => 'تحصيل رسوم الحجز الثابتة',
            Payment::TYPE_PLAN_PARTIAL => 'تحصيل رسوم الحجز على الباقات',
            Payment::TYPE_PLAN_FULL => 'تحصيل دفعة كلية من العميل',
            'app_fee' => 'تحصيل رسوم التطبيق من دفعة كلية',
            default => 'حركة واردة على محفظة التطبيق',
        };
    }

    private function isIncomingSource(string $source): bool
    {
        return in_array($source, [
            Payment::TYPE_RESERVATION_FEE,
            Payment::TYPE_PLAN_PARTIAL,
            Payment::TYPE_PLAN_FULL,
            'app_fee',
        ], true);
    }
}
