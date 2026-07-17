<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\GatewayWalletTransaction;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\UserRequest;
use App\Support\PaymentGatewayFees;
use App\Support\ReportCurrencyConverter;
use App\Support\Vat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class GatewayWalletAccountService
{
    private const GATEWAYS = [
        Payment::METHOD_TAP => [
            'label' => 'تاب',
            'title' => 'محفظة حساب تاب',
            'fee_percent' => 1.0,
            'fixed_fee_minor' => 0,
            'tone' => 'primary',
        ],
        Payment::METHOD_TABBY => [
            'label' => 'تابي',
            'title' => 'محفظة حساب تابي',
            'fee_percent' => 6.99,
            'fixed_fee_minor' => 150,
            'tone' => 'warning',
        ],
        Payment::METHOD_TAMARA => [
            'label' => 'تمارا',
            'title' => 'محفظة حساب تمارا',
            'fee_percent' => 6.99,
            'fixed_fee_minor' => 150,
            'tone' => 'danger',
        ],
    ];

    public function __construct(
        private readonly ReportCurrencyConverter $reportCurrencyConverter
    ) {}

    public function gateways(): array
    {
        return self::GATEWAYS;
    }

    public function gatewayConfig(string $gateway): array
    {
        abort_unless(array_key_exists($gateway, self::GATEWAYS), 404);

        return self::GATEWAYS[$gateway];
    }

    public function directionOptions(): array
    {
        return GatewayWalletTransaction::directionLabels();
    }

    public function sourceOptions(): array
    {
        return [
            'sales_package' => 'وارد: قيمة الباقات',
            'sales_reservation' => 'وارد: رسوم الحجز',
            GatewayWalletTransaction::SOURCE_BANK_DEPOSIT => 'وارد: تحويل الى البنك',
            GatewayWalletTransaction::SOURCE_APP_WALLET_TRANSFER => 'صادر: حساب محفظة التطبيق',
            GatewayWalletTransaction::SOURCE_GATEWAY_FEE => 'صادر: رسوم بوابة الدفع',
            GatewayWalletTransaction::SOURCE_OTHER => 'أخرى',
        ];
    }

    public function ledgerEntries(string $gateway, array $filters = []): Collection
    {
        $this->gatewayConfig($gateway);

        return $this->paymentEntries($gateway, $filters)
            ->concat($this->manualEntries($gateway, $filters))
            ->sortByDesc(fn (object $entry) => $entry->occurred_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function summary(string $gateway, array $filters = []): array
    {
        $entries = $this->ledgerEntries($gateway, $filters);
        $paymentEntries = $entries->where('entry_type', 'payment');
        $manualEntries = $entries->where('entry_type', 'manual');

        $salesMinor = (int) $paymentEntries->sum('amount_minor');
        $incomingMinor = (int) $manualEntries
            ->where('direction', GatewayWalletTransaction::DIRECTION_IN)
            ->sum('amount_minor');
        $transferMinor = (int) $manualEntries
            ->where('direction', GatewayWalletTransaction::DIRECTION_OUT)
            ->where('source_key', GatewayWalletTransaction::SOURCE_APP_WALLET_TRANSFER)
            ->sum('amount_minor');
        $manualFeeMinor = (int) $manualEntries
            ->where('direction', GatewayWalletTransaction::DIRECTION_OUT)
            ->where('source_key', GatewayWalletTransaction::SOURCE_GATEWAY_FEE)
            ->sum('amount_minor');
        $gatewayFeeMinor = (int) $paymentEntries->sum('fee_minor') + $manualFeeMinor;
        $vatMinor = (int) $paymentEntries->sum('vat_minor');

        return [
            'sales_minor' => $salesMinor,
            'incoming_minor' => $incomingMinor,
            'gateway_fee_minor' => $gatewayFeeMinor,
            'vat_minor' => $vatMinor,
            'remaining_gateway_minor' => $salesMinor - $incomingMinor - $gatewayFeeMinor - $vatMinor,
            'transfers_minor' => $transferMinor,
            'wallet_balance_minor' => $incomingMinor - $transferMinor,
            'operations_count' => $entries->count(),
        ];
    }

    private function paymentEntries(string $gateway, array $filters): Collection
    {
        $sourceFilter = $filters['source'] ?? null;

        if (($filters['direction'] ?? null) === GatewayWalletTransaction::DIRECTION_OUT) {
            return collect();
        }

        if (
            $sourceFilter !== null
            && ! in_array($sourceFilter, ['sales_package', 'sales_reservation'], true)
        ) {
            return collect();
        }

        return $this->paymentsQuery($gateway, $filters)
            ->get()
            ->filter(function (Payment $payment) use ($sourceFilter): bool {
                if ($sourceFilter === null) {
                    return true;
                }

                return $this->paymentSourceKey($payment) === $sourceFilter;
            })
            ->map(function (Payment $payment) use ($gateway): object {
                $amountMinor = $this->reportCurrencyConverter->convertMinor(
                    (int) $payment->amount_minor,
                    (string) $payment->currency
                );
                $feeMinor = $this->gatewayFeeMinor($gateway, $amountMinor);
                $sourceKey = $this->paymentSourceKey($payment);
                $request = $payment->userRequest;
                $vatMinor = $this->vatMinor($feeMinor, $request);

                return (object) [
                    'entry_type' => 'payment',
                    'reference_id' => $payment->gateway_reference ?: $payment->id,
                    'reference_label' => $payment->gateway_reference ?: '#'.substr((string) $payment->id, 0, 8),
                    'direction' => GatewayWalletTransaction::DIRECTION_IN,
                    'direction_label' => 'وارد',
                    'source_key' => $sourceKey,
                    'source_label' => $this->sourceOptions()[$sourceKey],
                    'description' => $this->paymentDescription($payment),
                    'counterparty' => $payment->user?->name ?? 'غير معروف',
                    'country' => $request?->country?->name ?? '—',
                    'order_notes' => collect([
                        $request?->formatted_order_number ? '#'.$request->formatted_order_number : null,
                        'وسيلة الدفع: '.strtoupper((string) $payment->payment_method),
                    ])->filter()->implode(PHP_EOL),
                    'amount_minor' => $amountMinor,
                    'fee_minor' => $feeMinor,
                    'vat_minor' => $vatMinor,
                    'net_minor' => $amountMinor - $feeMinor - $vatMinor,
                    'currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => $payment->gateway_status ?: '—',
                    'occurred_at' => $payment->created_at,
                ];
            });
    }

    private function manualEntries(string $gateway, array $filters): Collection
    {
        $sourceFilter = $filters['source'] ?? null;

        if (
            $sourceFilter !== null
            && in_array($sourceFilter, ['sales_package', 'sales_reservation'], true)
        ) {
            return collect();
        }

        return $this->manualQuery($gateway, $filters)
            ->get()
            ->map(function (GatewayWalletTransaction $transaction): object {
                $isIncoming = $transaction->direction === GatewayWalletTransaction::DIRECTION_IN;
                $amountMinor = (int) $transaction->amount_minor;

                return (object) [
                    'entry_type' => 'manual',
                    'reference_id' => $transaction->id,
                    'reference_label' => '#'.substr((string) $transaction->id, 0, 8),
                    'direction' => $transaction->direction,
                    'direction_label' => $isIncoming ? 'وارد' : 'صادر',
                    'source_key' => (string) $transaction->source,
                    'source_label' => GatewayWalletTransaction::sourceLabelFor($transaction->source),
                    'description' => $transaction->creator?->name ?? 'الإدارة',
                    'counterparty' => $transaction->creator?->name ?? 'الإدارة',
                    'country' => '—',
                    'order_notes' => $transaction->notes ?: '—',
                    'amount_minor' => $amountMinor,
                    'fee_minor' => 0,
                    'vat_minor' => 0,
                    'net_minor' => $isIncoming ? $amountMinor : -$amountMinor,
                    'currency' => ReportCurrencyConverter::REPORT_CURRENCY,
                    'notes' => $transaction->notes ?: '—',
                    'occurred_at' => $transaction->created_at,
                ];
            });
    }

    private function paymentsQuery(string $gateway, array $filters): Builder
    {
        return Payment::query()
            ->with(['user', 'userRequest.country', 'userRequest.trainer', 'userRequest.plan.country'])
            ->where('payment_method', $gateway)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereIn('type', [
                Payment::TYPE_RESERVATION_FEE,
                Payment::TYPE_PLAN_PARTIAL,
                Payment::TYPE_PLAN_FULL,
            ])
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.$search.'%';
                $normalizedOrderNumber = UserRequest::normalizeOrderNumberSearch($search);

                $builder->where(function (Builder $nested) use ($like, $normalizedOrderNumber): void {
                    $nested
                        ->where('id', 'like', $like)
                        ->orWhere('gateway_reference', 'like', $like)
                        ->orWhere('gateway_status', 'like', $like)
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

    private function manualQuery(string $gateway, array $filters): Builder
    {
        return GatewayWalletTransaction::query()
            ->with('creator')
            ->where('gateway', $gateway)
            ->when($filters['direction'] ?? null, fn (Builder $builder, string $direction) => $builder->where('direction', $direction))
            ->when($filters['source'] ?? null, function (Builder $builder, string $source): void {
                if (array_key_exists($source, GatewayWalletTransaction::sourceLabels())) {
                    $builder->where('source', $source);
                }
            })
            ->when($filters['from'] ?? null, fn (Builder $builder, $from) => $builder->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, $to) => $builder->where('created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.$search.'%';

                $builder->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('id', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhere('source', 'like', $like)
                        ->orWhereHas('creator', function (Builder $creatorQuery) use ($like): void {
                            $creatorQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone_with_cc', 'like', $like);
                        });
                });
            });
    }

    private function paymentSourceKey(Payment $payment): string
    {
        return $payment->type === Payment::TYPE_PLAN_FULL ? 'sales_package' : 'sales_reservation';
    }

    private function paymentDescription(Payment $payment): string
    {
        $request = $payment->userRequest;

        return collect([
            $payment->user?->name ? 'تحصيل من العميل: '.$payment->user->name : 'تحصيل من عميل',
            $request?->trainer?->name ? 'المدرب: '.$request->trainer->name : null,
            $request?->plan?->title ? 'الباقة: '.$request->plan->title : null,
        ])->filter()->implode(' | ');
    }

    private function gatewayFeeMinor(string $gateway, int $amountMinor): int
    {
        $config = $this->feeConfig($gateway);

        return (int) round($amountMinor * (((float) $config['commission_percent']) / 100))
            + (int) $config['fixed_fee_minor'];
    }

    private function vatMinor(int $feeMinor, ?UserRequest $request): int
    {
        return (int) round($feeMinor * (Vat::percentForRequest($request) / 100));
    }

    private function feeConfig(string $gateway): array
    {
        $stored = Setting::query()->where('key', PaymentGatewayFees::SETTINGS_KEY)->value('value');

        if (is_string($stored) && trim($stored) !== '') {
            $row = collect(PaymentGatewayFees::rows($stored))->firstWhere('gateway', $gateway);

            if (is_array($row)) {
                return [
                    'fixed_fee_minor' => (int) $row['fixed_fee_minor'],
                    'commission_percent' => (float) $row['commission_percent'],
                ];
            }
        }

        $gatewayConfig = $this->gatewayConfig($gateway);

        return [
            'fixed_fee_minor' => (int) $gatewayConfig['fixed_fee_minor'],
            'commission_percent' => (float) $gatewayConfig['fee_percent'],
        ];
    }
}
