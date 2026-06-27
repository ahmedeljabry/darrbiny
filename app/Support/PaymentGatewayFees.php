<?php

declare(strict_types=1);

namespace App\Support;

final class PaymentGatewayFees
{
    public const SETTINGS_KEY = 'payment.gateway_fees';

    public const DEFAULT_FIXED_FEE_MINOR = 150;

    public const DEFAULT_COMMISSION_PERCENT = 7.0;

    public const GATEWAYS = [
        'tap' => 'تاب',
        'tabby' => 'تابي',
        'tamara' => 'تمارا',
    ];

    public static function rows(string|array|null $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $submittedRows = collect($value ?? [])
            ->filter(static fn (mixed $row): bool => is_array($row))
            ->keyBy(static fn (array $row): string => (string) ($row['gateway'] ?? ''));

        return collect(self::GATEWAYS)
            ->map(static function (string $label, string $gateway) use ($submittedRows): array {
                $row = $submittedRows->get($gateway, []);

                return [
                    'gateway' => $gateway,
                    'label' => $label,
                    'fixed_fee_minor' => self::fixedFeeMinor($row['fixed_fee_minor'] ?? null),
                    'commission_percent' => self::commissionPercent($row['commission_percent'] ?? null),
                    'country_id' => self::countryId($row['country_id'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    public static function encode(string|array|null $value): string
    {
        return json_encode(self::rows($value), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function keys(): array
    {
        return array_keys(self::GATEWAYS);
    }

    private static function fixedFeeMinor(mixed $value): int
    {
        if (! is_numeric($value)) {
            return self::DEFAULT_FIXED_FEE_MINOR;
        }

        return max(0, (int) $value);
    }

    private static function commissionPercent(mixed $value): float
    {
        if (! is_numeric($value)) {
            return self::DEFAULT_COMMISSION_PERCENT;
        }

        return round(max(0.0, min(100.0, (float) $value)), 2);
    }

    private static function countryId(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
