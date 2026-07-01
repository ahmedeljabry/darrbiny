<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Payment;
use App\Models\Setting;

final class PaymentMethodSettings
{
    public const DEFAULT_VISIBLE = true;

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

    public static function mobilePayload(): array
    {
        return collect(self::gatewayLabels())
            ->map(static fn (string $label, string $method): array => [
                'key' => $method,
                'label' => $label,
                'enabled' => self::isVisibleInApp($method),
            ])
            ->values()
            ->all();
    }

    public static function visibilitySettingKey(string $method): string
    {
        return "payment.{$method}.enabled";
    }
}
