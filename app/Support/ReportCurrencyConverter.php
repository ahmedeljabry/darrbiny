<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReportCurrencyConverter
{
    public const REPORT_CURRENCY = 'SAR';
    private const SETTINGS_KEY = 'reports.exchange_rates_to_sar';

    private ?array $rates = null;

    public function rates(): array
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $stored = Setting::query()->where('key', self::SETTINGS_KEY)->value('value');
        $decoded = json_decode(is_string($stored) ? $stored : '[]', true);
        $decoded = is_array($decoded) ? $decoded : [];

        $rates = collect($decoded)
            ->mapWithKeys(function ($value, $currency): array {
                $currency = strtoupper(trim((string) $currency));
                $rate = is_numeric($value) ? (float) $value : null;

                if ($currency === '' || $rate === null || $rate <= 0) {
                    return [];
                }

                return [$currency => $rate];
            })
            ->all();

        $rates[self::REPORT_CURRENCY] = 1.0;

        ksort($rates);

        return $this->rates = $rates;
    }

    public function rateFor(?string $currency): float
    {
        $currency = strtoupper(trim((string) $currency));

        if ($currency === '' || $currency === self::REPORT_CURRENCY) {
            return 1.0;
        }

        return (float) ($this->rates()[$currency] ?? 1.0);
    }

    public function convertMinor(int $amountMinor, ?string $currency): int
    {
        return (int) round($amountMinor * $this->rateFor($currency));
    }

    public function convertReportMinorToOriginal(int $amountMinor, ?string $currency): int
    {
        $currency = strtoupper(trim((string) $currency));
        $rate = $this->rateFor($currency);

        if ($currency === '' || $currency === self::REPORT_CURRENCY || $rate <= 0) {
            return $amountMinor;
        }

        return (int) round($amountMinor / $rate);
    }

    public function formatReportMinor(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2) . ' ' . self::REPORT_CURRENCY;
    }

    public function formatConvertedMinor(int $amountMinor, ?string $currency): string
    {
        return $this->formatReportMinor($this->convertMinor($amountMinor, $currency));
    }

    public function formatOriginalMinor(int $amountMinor, ?string $currency): string
    {
        return number_format($amountMinor / 100, 2) . ' ' . strtoupper(trim((string) ($currency ?: self::REPORT_CURRENCY)));
    }

    public function convertGroupedMinorSumsToReportCurrency(Builder $query, string $column): int
    {
        return (clone $query)
            ->selectRaw("currency, COALESCE(SUM({$column}), 0) as total_minor")
            ->groupBy('currency')
            ->get()
            ->sum(fn ($row) => $this->convertMinor((int) $row->total_minor, (string) $row->currency));
    }

    public function sumCollectionMinorToReportCurrency(
        Collection $items,
        string $amountKey = 'amount_minor',
        string $currencyKey = 'currency'
    ): int {
        return $items->sum(fn ($item) => $this->convertMinor(
            (int) data_get($item, $amountKey, 0),
            (string) data_get($item, $currencyKey, self::REPORT_CURRENCY)
        ));
    }

    public function uniqueCurrencies(Collection $currencies): array
    {
        return $currencies
            ->map(fn ($currency) => strtoupper(trim((string) $currency)))
            ->filter()
            ->push(self::REPORT_CURRENCY)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
