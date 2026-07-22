<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Country;
use App\Models\Payment;
use App\Models\UserRequest;

final class Vat
{
    public static function percentForCountry(?Country $country): float
    {
        if (! $country) {
            return 0.0;
        }

        return min(100.0, max(0.0, (float) $country->vat_percent));
    }

    public static function percentForRequest(?UserRequest $request): float
    {
        return self::percentForCountry(self::countryForRequest($request));
    }

    public static function percentForPayment(Payment $payment): float
    {
        $payment->loadMissing(['userRequest.country', 'userRequest.plan.country']);

        return self::percentForRequest($payment->userRequest);
    }

    public static function minorForAmount(int $amountMinor, ?UserRequest $request): int
    {
        return (int) round($amountMinor * (self::percentForRequest($request) / 100));
    }

    public static function minorForPayment(Payment $payment): int
    {
        $payment->loadMissing(['userRequest.country', 'userRequest.plan.country']);

        return self::minorForAmount((int) $payment->amount_minor, $payment->userRequest);
    }

    public static function grossMinorForPayment(Payment $payment): int
    {
        return (int) $payment->amount_minor + self::minorForPayment($payment);
    }

    public static function formattedGrossGatewayAmount(Payment $payment): string
    {
        return number_format(self::grossMinorForPayment($payment) / 100, 2, '.', '');
    }

    public static function formattedGatewayAmount(Payment $payment): string
    {
        return number_format(self::minorForPayment($payment) / 100, 2, '.', '');
    }

    private static function countryForRequest(?UserRequest $request): ?Country
    {
        if (! $request) {
            return null;
        }

        $request->loadMissing(['country', 'plan.country']);

        return $request->country ?: $request->plan?->country;
    }
}
