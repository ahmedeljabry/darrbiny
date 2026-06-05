<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AppExpense;
use App\Models\AppWalletTransaction;
use App\Models\Payment;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
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
            Payment::TYPE_PLAN_PARTIAL => 'وارد: رسوم الحجز',
            Payment::TYPE_PLAN_FULL => 'وارد: قيمة الباقات',
            'app_fee' => 'تحليل: رسوم الباقات',
            AppWalletTransaction::SOURCE_MANUAL_DEPOSIT => 'وارد: إيداع محفظة التطبيق',
            AppExpense::TYPE_OPERATING_EXPENSE => 'صادر: مصروفات تشغيل',
            AppWalletTransaction::SOURCE_TRAINER_DUES_WITHDRAWAL => 'صادر: سحب مستحقات مدرب',
            AppWalletTransaction::SOURCE_PACKAGE_REFUND_WITHDRAWAL => 'صادر: سحب استرداد باقة ملغية',
            AppWalletTransaction::SOURCE_PROFIT_WITHDRAWAL => 'صادر: سحب أرباح',
            WalletTransaction::TYPE_WITHDRAW_REQUEST => 'صادر: طلبات السحب المنفذة',
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

            $incomingMinor += (int) $this->incomingManualTransactionsQuery($filters)->sum('amount_minor');
        }

        if (($filters['direction'] ?? null) !== 'in') {
            $outgoingMinor = (int) $this->outgoingExpensesQuery($filters)->sum('amount_minor')
                + (int) $this->outgoingManualTransactionsQuery($filters)->sum('amount_minor')
                + $this->approvedWalletWithdrawalsReportAmountMinor($filters);
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
            ->concat($this->manualAppWalletEntries($filters))
            ->concat($this->outgoingEntries($filters))
            ->concat($this->approvedWalletWithdrawalEntries($filters))
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
                    'order_reference' => $payment->userRequest?->formatted_order_number
                        ? '#' . $payment->userRequest->formatted_order_number
                        : '—',
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

    private function manualAppWalletEntries(array $filters): Collection
    {
        $sourceFilter = $filters['source'] ?? null;

        if ($sourceFilter !== null && ! array_key_exists($sourceFilter, AppWalletTransaction::sourceLabels())) {
            return collect();
        }

        return $this->manualTransactionsQuery($filters)
            ->get()
            ->map(function (AppWalletTransaction $transaction): object {
                $isIncoming = $transaction->direction === AppWalletTransaction::DIRECTION_IN;

                return (object) [
                    'reference_id' => $transaction->id,
                    'reference_label' => '#' . substr((string) $transaction->id, 0, 8),
                    'direction' => $transaction->direction,
                    'direction_label' => $isIncoming ? 'وارد' : 'صادر',
                    'source_key' => (string) $transaction->source,
                    'source_label' => $this->entrySourceLabel((string) $transaction->source),
                    'description' => AppWalletTransaction::sourceLabelFor($transaction->source),
                    'order_reference' => '—',
                    'counterparty' => $transaction->creator?->name ?? 'الإدارة',
                    'details' => 'حركة يدوية على محفظة التطبيق',
                    'amount_minor' => (int) $transaction->amount_minor,
                    'report_amount_minor' => (int) $transaction->amount_minor,
                    'currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'report_currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => $transaction->notes ?: '—',
                    'occurred_at' => $transaction->created_at,
                ];
            });
    }

    private function outgoingEntries(array $filters): Collection
    {
        if (($filters['direction'] ?? null) === 'in') {
            return collect();
        }

        $expenseEntries = $this->outgoingExpensesQuery($filters)
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

        return $expenseEntries;
    }

    private function approvedWalletWithdrawalEntries(array $filters): Collection
    {
        if (($filters['direction'] ?? null) === 'in') {
            return collect();
        }

        return $this->approvedWalletWithdrawalsQuery($filters)
            ->get()
            ->map(function (WalletTransaction $transaction): object {
                $user = $transaction->user;
                $isTrainer = ($user?->user_type?->value ?? null) === 'captain';
                $reportAmountMinor = $transaction->reportAmountMinor($this->reportCurrencyConverter);

                return (object) [
                    'reference_id' => $transaction->id,
                    'reference_label' => '#' . substr((string) $transaction->id, 0, 8),
                    'direction' => 'out',
                    'direction_label' => 'صادر',
                    'source_key' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
                    'source_label' => $this->entrySourceLabel(WalletTransaction::TYPE_WITHDRAW_REQUEST),
                    'description' => 'تنفيذ طلب سحب من قسم العمليات',
                    'order_reference' => '—',
                    'counterparty' => $user?->name ?? 'غير معروف',
                    'details' => collect([
                        $user?->phone_with_cc ? 'الجوال: ' . $user->phone_with_cc : null,
                        'نوع الحساب: ' . ($isTrainer ? 'مدرب' : 'طالب'),
                        $transaction->processedBy?->name ? 'تم التنفيذ بواسطة: ' . $transaction->processedBy->name : null,
                    ])->filter()->implode(' | ') ?: '—',
                    'amount_minor' => $transaction->amountMinor(),
                    'report_amount_minor' => $reportAmountMinor,
                    'currency' => $transaction->transactionCurrency(),
                    'report_currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => $transaction->notes ?: '—',
                    'occurred_at' => $transaction->processed_at ?? $transaction->updated_at ?? $transaction->created_at,
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
        } elseif ($source !== null) {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%' . $search . '%';
                $normalizedOrderNumber = UserRequest::normalizeOrderNumberSearch($search);

                $builder->where(function (Builder $nested) use ($like, $normalizedOrderNumber): void {
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
                        ->orWhereHas('userRequest.plan', fn (Builder $planQuery) => $planQuery->where('title', 'like', $like))
                        ->orWhereHas('userRequest', function (Builder $requestQuery) use ($like, $normalizedOrderNumber): void {
                            $requestQuery
                                ->where('id', 'like', $like)
                                ->orWhereRaw('CAST(order_number as CHAR) like ?', [$like]);

                            if ($normalizedOrderNumber !== null) {
                                $requestQuery->orWhere('order_number', $normalizedOrderNumber);
                            }
                        });
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
            } else {
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

    private function manualTransactionsQuery(array $filters): Builder
    {
        $source = $filters['source'] ?? null;

        $query = AppWalletTransaction::query()->with('creator');

        if ($source !== null) {
            if (array_key_exists($source, AppWalletTransaction::sourceLabels())) {
                $query->where('source', $source);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query
            ->when($filters['direction'] ?? null, fn (Builder $builder, string $direction) => $builder->where('direction', $direction))
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
                        ->orWhere('source', 'like', $like);
                });
            });
    }

    private function incomingManualTransactionsQuery(array $filters): Builder
    {
        return $this->manualTransactionsQuery($filters)
            ->where('direction', AppWalletTransaction::DIRECTION_IN);
    }

    private function outgoingManualTransactionsQuery(array $filters): Builder
    {
        return $this->manualTransactionsQuery($filters)
            ->where('direction', AppWalletTransaction::DIRECTION_OUT);
    }

    private function approvedWalletWithdrawalsQuery(array $filters): Builder
    {
        $source = $filters['source'] ?? null;

        $query = WalletTransaction::query()
            ->with(['user.country', 'user.bankCountry', 'processedBy'])
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->where('status', WalletTransaction::STATUS_APPROVED);

        if ($source !== null && $source !== WalletTransaction::TYPE_WITHDRAW_REQUEST) {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('processed_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('processed_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%' . $search . '%';

                $builder->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('id', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                            $userQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        })
                        ->orWhereHas('processedBy', function (Builder $processorQuery) use ($like): void {
                            $processorQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        });
                });
            });
    }

    private function approvedWalletWithdrawalsReportAmountMinor(array $filters): int
    {
        return $this->approvedWalletWithdrawalsQuery($filters)
            ->get()
            ->sum(fn (WalletTransaction $transaction) => $transaction->reportAmountMinor($this->reportCurrencyConverter));
    }

    private function entrySourceLabel(string $sourceKey): string
    {
        return $this->sourceOptions()[$sourceKey] ?? $sourceKey;
    }

    private function incomingDescription(string $sourceKey): string
    {
        return match ($sourceKey) {
            Payment::TYPE_RESERVATION_FEE => 'تحصيل رسوم الحجز الثابتة',
            Payment::TYPE_PLAN_PARTIAL => 'تحصيل رسوم الحجز',
            Payment::TYPE_PLAN_FULL => 'تحصيل قيمة الباقة من العميل',
            'app_fee' => 'تحليل رسوم الباقات من الدفعات الكلية',
            default => 'حركة واردة على محفظة التطبيق',
        };
    }
}
