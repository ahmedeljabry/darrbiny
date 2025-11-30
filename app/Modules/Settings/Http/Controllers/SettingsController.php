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
        $reservationFeeMinor = Fees::reservationFeeMinor();
        $appFeePercent = Fees::appFeePercent();
        
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
            ],
        ]);
    }
}

