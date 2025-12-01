<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Support\Fees;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class SettingsController extends BaseController
{
    public function fees(Request $request): JsonResponse
    {
        $countryId = $request->query('country_id');
        $reservationFeeMinor = Fees::reservationFeeMinor($countryId);
        $appFeePercent = Fees::appFeePercent();
        
        $countries = \App\Models\Country::select('id', 'name', 'iso2', 'currency', 'reservation_fee_minor')
            ->orderBy('name')
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'iso2' => $country->iso2,
                    'currency' => $country->currency,
                    'reservation_fee' => [
                        'minor' => (int) ($country->reservation_fee_minor ?? Fees::reservationFeeMinor(null)),
                        'amount' => ($country->reservation_fee_minor ?? Fees::reservationFeeMinor(null)) / 100,
                    ],
                ];
            });
        
        return response()->json([
            'fees' => [
                'reservation_fee' => [
                    'minor' => $reservationFeeMinor,
                    'amount' => $reservationFeeMinor / 100,
                    'description' => 'رسوم الحجز (الجاهزة) - قيمة ثابتة تدفع في البداية وقبل إرسال أي طلب',
                    'type' => 'fixed',
                ],
                'app_fee' => [
                    'percent' => $appFeePercent,
                    'description' => 'رسوم التطبيق (النسبة) - نسبة معينة تخصم مباشرة من قيمة الباقة المحولة للمدرب',
                    'type' => 'percentage',
                ],
                'countries' => $countries,
            ],
        ]);
    }
}

