<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

final class Fees
{
    public static function reservationFeeMinor(?string $countryId = null): int
    {
        if ($countryId) {
            $country = \App\Models\Country::find($countryId);
            if ($country && $country->reservation_fee_minor !== null) {
                return (int) $country->reservation_fee_minor;
            }
        }
        
        return (int) (Setting::where('key', 'fees.reservation_fee_minor')->value('value')
            ?? config('app.reservation_fee_minor', 1000));
    }

    public static function appFeePercent(): float
    {
        return (float) (Setting::where('key', 'fees.app_fee_percent')->value('value')
            ?? config('app.app_fee_percent', 10.0));
    }
}
