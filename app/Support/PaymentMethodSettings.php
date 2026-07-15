<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\UserRequest;

final class PaymentMethodSettings
{
    public const DEFAULT_VISIBLE = true;

    private const COUNTRY_LIMITED_METHODS = [
        Payment::METHOD_TABBY => [
            'countries' => ['SA'],
            'currencies' => ['SAR'],
        ],
        Payment::METHOD_TAMARA => [
            'countries' => ['SA'],
            'currencies' => ['SAR'],
        ],
    ];

    public static function gatewayLabels(): array
    {
        return [
            Payment::METHOD_TAP => 'تاب',
            Payment::METHOD_TABBY => 'تابي',
            Payment::METHOD_TAMARA => 'تمارا',
        ];
    }

    public static function appVisibilityMethods(): array
    {
        return [
            Payment::METHOD_TABBY,
            Payment::METHOD_TAMARA,
        ];
    }

    public static function isVisibleInApp(string $method): bool
    {
        if (! in_array($method, self::appVisibilityMethods(), true)) {
            return true;
        }

        $value = Setting::query()
            ->where('key', self::visibilitySettingKey($method))
            ->value('value');

        if ($value === null || $value === '') {
            return self::DEFAULT_VISIBLE;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function mobilePayload(?string $countryId = null): array
    {
        $country = $countryId
            ? Country::query()->find($countryId)
            : null;

        return collect(self::gatewayLabels())
            ->map(static fn (string $label, string $method): array => [
                'key' => $method,
                'label' => $label,
                'enabled' => self::isVisibleInApp($method) && self::supportsCountry($method, $country),
            ])
            ->values()
            ->all();
    }

    public static function isAvailableForRequest(string $method, UserRequest $request): bool
    {
        $request->loadMissing('country');

        return self::isVisibleInApp($method)
            && self::supportsCountry($method, $request->country, (string) $request->currency);
    }

    public static function visibilitySettingKey(string $method): string
    {
        return "payment.{$method}.enabled";
    }

    private static function supportsCountry(string $method, ?Country $country, ?string $currency = null): bool
    {
        $limits = self::COUNTRY_LIMITED_METHODS[$method] ?? null;
        if ($limits === null) {
            return true;
        }

        if ($country === null && ($currency === null || trim($currency) === '')) {
            return true;
        }

        $currency = strtoupper(trim((string) ($currency ?: $country?->currency)));
        if ($currency !== '' && ! in_array($currency, $limits['currencies'], true)) {
            return false;
        }

        $iso2 = strtoupper(trim((string) $country?->iso2));
        if ($iso2 !== '' && ! in_array($iso2, $limits['countries'], true)) {
            return false;
        }

        return $currency !== '';
    }
}
