<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Models\Setting;
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

    public function roles(): JsonResponse
    {
        $trainerRoles = $this->getListSetting('roles.trainer');
        $trainerRestrictions = $this->getListSetting('restrictions.trainer');
        $userRoles = $this->getListSetting('roles.user');
        $userRestrictions = $this->getListSetting('restrictions.user');

        return response()->json([
            'roles' => [
                'trainer' => [
                    'roles' => $trainerRoles,
                    'restrictions' => $trainerRestrictions,
                ],
                'user' => [
                    'roles' => $userRoles,
                    'restrictions' => $userRestrictions,
                ],
                'student' => [
                    'roles' => $userRoles,
                    'restrictions' => $userRestrictions,
                ],
            ],
        ]);
    }

    public function pages(Request $request): JsonResponse
    {
        $countryId = $request->query('country_id');
        $reservationFeeMinor = Fees::reservationFeeMinor($countryId);
        $appFeePercent = Fees::appFeePercent();

        return response()->json([
            'pages' => [
                'terms' => $this->getStringSetting('pages.terms'),
                'terms_user' => $this->getStringSetting('pages.terms'),
                'terms_trainer' => $this->getStringSetting('pages.terms_trainer'),
                'privacy' => $this->getStringSetting('pages.privacy'),
                'usage' => $this->getStringSetting('pages.usage'),
                'about' => $this->getStringSetting('pages.about'),
                'sales' => $this->getStringSetting('pages.sales'),
                'app_fees_user' => $this->getStringSetting('pages.sales'),
                'app_fees_trainer' => $this->getStringSetting('pages.sales_trainer'),
            ],
            'sales_fees' => [
                'reservation_fee' => [
                    'minor' => $reservationFeeMinor,
                    'amount' => $reservationFeeMinor / 100,
                ],
                'app_fee' => [
                    'percent' => $appFeePercent,
                ],
            ],
        ]);
    }

    private function getListSetting(string $key): array
    {
        $raw = Setting::where('key', $key)->value('value');

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(static fn ($value) => trim((string) $value))
            ->filter(static fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function getStringSetting(string $key): string
    {
        $value = Setting::where('key', $key)->value('value');
        return is_string($value) ? $value : '';
    }
}

