<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class WalletCurrency
{
    public static function forUser(?User $user): string
    {
        if (! $user) {
            return ReportCurrencyConverter::REPORT_CURRENCY;
        }

        $countryCurrency = self::countryCurrencyForUser($user);

        if ($countryCurrency !== null) {
            return $countryCurrency;
        }

        $currency = strtoupper(trim((string) (
            $user->currency
            ?: ReportCurrencyConverter::REPORT_CURRENCY
        )));

        return $currency !== '' ? $currency : ReportCurrencyConverter::REPORT_CURRENCY;
    }

    public static function countryCurrencyForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing(['bankCountry', 'country']);

        $currency = strtoupper(trim((string) (
            $user->bankCountry?->currency
            ?: $user->country?->currency
        )));

        return $currency !== '' ? $currency : null;
    }
}
