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
        $contactPage = $this->getStringSetting('pages.contact');
        $appUsageTrainer = $this->getFaqSetting('pages.app_usage_trainer');
        $appUsageStudent = $this->getFaqSetting('pages.app_usage_student');

        return response()->json([
            'pages' => [
                'terms' => $this->getFaqSetting('pages.terms'),
                'terms_user' => $this->getFaqSetting('pages.terms'),
                'terms_trainer' => $this->getFaqSetting('pages.terms_trainer'),
                'privacy' => $this->getFaqSetting('pages.privacy'),
                'usage' => $this->getFaqSetting('pages.usage'),
                'about' => $this->getFaqSetting('pages.about'),
                'exchange' => $this->getFaqSetting('pages.exchange'),
                'sales' => $this->getFaqSetting('pages.sales'),
                'app_fees_user' => $this->getFaqSetting('pages.sales'),
                'app_fees_trainer' => $this->getFaqSetting('pages.sales_trainer'),
                'app_usage_trainer' => $appUsageTrainer,
                'app_usage_student' => $appUsageStudent,
                'trainer_usage_guide' => $appUsageTrainer,
                'student_usage_guide' => $appUsageStudent,
                'faq' => $this->getFaqSetting('pages.faq'),
                'contact' => $contactPage,
                'contact_us' => $contactPage,
                'contact-us' => $contactPage,
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

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
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

    private function getFaqSetting(string $key): array
    {
        $value = Setting::where('key', $key)->value('value');

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return [['question' => '', 'answer' => $value]];
        }

        return collect($decoded)
            ->map(static function ($row) {
                if (! is_array($row)) {
                    return ['question' => '', 'answer' => trim((string) $row)];
                }
                $question = trim((string) ($row['question'] ?? ''));
                $answer = trim((string) ($row['answer'] ?? ''));

                return ($question === '' && $answer === '') ? null : [
                    'question' => $question,
                    'answer' => $answer,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
