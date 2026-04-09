<?php

declare(strict_types=1);

namespace App\Support;

final class WalletAmount
{
    public static function majorToMinor(int|float|string|null $amount): int
    {
        if ($amount === null) {
            return 0;
        }

        $normalized = str_replace(',', '', trim((string) $amount));
        if ($normalized === '') {
            return 0;
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function minorToMajor(int $amountMinor): float
    {
        return round($amountMinor / 100, 2);
    }

    public static function formatMinor(int $amountMinor, int $decimals = 2): string
    {
        return number_format(self::minorToMajor($amountMinor), $decimals);
    }

    public static function formatMajor(int|float|string|null $amount, int $decimals = 2, bool $trimTrailingZeros = false): string
    {
        $formatted = number_format((float) ($amount ?? 0), $decimals, '.', ',');

        if (! $trimTrailingZeros) {
            return $formatted;
        }

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
