<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Services\Admin\SettingsService;
use App\Support\PaymentGatewayFees;
use App\Support\ReportCurrencyConverter;
use Illuminate\Routing\Controller as BaseController;

class SettingsController extends BaseController
{
    public function index(SettingsService $service)
    {
        $settings = $service->allKeyed();
        $countries = Country::orderBy('name')->get();
        $trainerRoles = $this->decodeListSetting($settings['roles.trainer'] ?? null);
        $trainerRestrictions = $this->decodeListSetting($settings['restrictions.trainer'] ?? null);
        $userRoles = $this->decodeListSetting($settings['roles.user'] ?? null);
        $userRestrictions = $this->decodeListSetting($settings['restrictions.user'] ?? null);
        $paymentGatewayFees = old('payment_gateway_fees');
        if (! is_array($paymentGatewayFees)) {
            $paymentGatewayFees = PaymentGatewayFees::rows($settings[PaymentGatewayFees::SETTINGS_KEY] ?? null, $countries);
        }
        $reportCurrency = ReportCurrencyConverter::REPORT_CURRENCY;
        $paymentCurrencies = Payment::query()
            ->whereNotNull('currency')
            ->where('currency', '<>', '')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency')
            ->map(fn ($currency) => strtoupper(trim((string) $currency)))
            ->merge(
                $countries->pluck('currency')->map(fn ($currency) => strtoupper(trim((string) $currency)))
            )
            ->filter(fn ($currency) => $currency !== '' && $currency !== $reportCurrency)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $reportExchangeRates = old('report_exchange_rates');

        if (! is_array($reportExchangeRates)) {
            $reportExchangeRates = $this->decodeExchangeRates(
                $settings['reports.exchange_rates_to_sar'] ?? null,
                $paymentCurrencies
            );
        }

        return view('admin.settings.index', compact(
            'settings',
            'countries',
            'trainerRoles',
            'trainerRestrictions',
            'userRoles',
            'userRestrictions',
            'paymentGatewayFees',
            'reportCurrency',
            'paymentCurrencies',
            'reportExchangeRates'
        ));
    }

    public function update(SettingsUpdateRequest $request, SettingsService $service)
    {
        $service->update(
            $request->validated(),
            $request->file('logo'),
            $request->file('video_app_file'),
            $request->file('favicon'),
            $request->file('video_captain_file'),
        );
        return back()->with('status','تم حفظ الإعدادات');
    }

    private function decodeListSetting(?string $value): array
    {
        $items = json_decode($value ?? '[]', true);
        $items = is_array($items) ? $items : [];
        return empty($items) ? [''] : $items;
    }

    private function decodeExchangeRates(?string $value, array $suggestedCurrencies = []): array
    {
        $decoded = json_decode($value ?? '[]', true);
        $decoded = is_array($decoded) ? $decoded : [];

        $rows = collect($decoded)
            ->map(function ($rate, $currency): ?array {
                $currency = strtoupper(trim((string) $currency));
                $rate = is_numeric($rate) ? number_format((float) $rate, 6, '.', '') : '';

                if ($currency === '' || $currency === ReportCurrencyConverter::REPORT_CURRENCY) {
                    return null;
                }

                return [
                    'currency' => $currency,
                    'rate' => $rate,
                ];
            })
            ->filter()
            ->values();

        $knownCurrencies = $rows->pluck('currency')->all();

        foreach ($suggestedCurrencies as $currency) {
            if (! in_array($currency, $knownCurrencies, true)) {
                $rows->push([
                    'currency' => $currency,
                    'rate' => '',
                ]);
            }
        }

        return $rows->isNotEmpty()
            ? $rows->all()
            : [['currency' => '', 'rate' => '']];
    }
}
