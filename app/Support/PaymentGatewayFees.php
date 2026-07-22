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

    public static function rows(string|array|null $value, iterable $countries = []): array
    {
        $storedRows = self::submittedRows($value);
        $countryRows = self::countryRows($countries);

        if ($countryRows !== []) {
            $rows = [];

            foreach (self::GATEWAYS as $gateway => $label) {
                foreach ($countryRows as $country) {
                    $config = self::configForRows($storedRows, $gateway, $country['id']);
                    $rows[] = [
                        'gateway' => $gateway,
                        'label' => $label,
                        'fixed_fee_minor' => $config['fixed_fee_minor'],
                        'commission_percent' => $config['commission_percent'],
                        'country_id' => $country['id'],
                        'country_name' => $country['name'],
                        'currency' => $country['currency'],
                    ];
                }
            }

            return $rows;
        }

        return collect(self::GATEWAYS)
            ->map(static function (string $label, string $gateway) use ($storedRows): array {
                $config = self::configForRows($storedRows, $gateway, null);

                return [
                    'gateway' => $gateway,
                    'label' => $label,
                    'fixed_fee_minor' => $config['fixed_fee_minor'],
                    'commission_percent' => $config['commission_percent'],
                    'country_id' => $config['country_id'],
                ];
            })
            ->values()
            ->all();
    }

    public static function encode(string|array|null $value): string
    {
        return json_encode(self::submittedRows($value), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function configFor(string|array|null $value, string $gateway, ?string $countryId = null): array
    {
        return self::configForRows(self::submittedRows($value), $gateway, self::countryId($countryId));
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

    private static function submittedRows(string|array|null $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        return collect($value ?? [])
            ->filter(static fn (mixed $row): bool => is_array($row))
            ->map(static function (array $row): ?array {
                $gateway = (string) ($row['gateway'] ?? '');
                if (! array_key_exists($gateway, self::GATEWAYS)) {
                    return null;
                }

                return [
                    'gateway' => $gateway,
                    'fixed_fee_minor' => self::fixedFeeMinor($row['fixed_fee_minor'] ?? null),
                    'commission_percent' => self::commissionPercent($row['commission_percent'] ?? null),
                    'country_id' => self::countryId($row['country_id'] ?? null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function configForRows(array $rows, string $gateway, ?string $countryId): array
    {
        $countryId = self::countryId($countryId);

        $match = collect($rows)->first(static fn (array $row): bool => $row['gateway'] === $gateway && $row['country_id'] === $countryId)
            ?? collect($rows)->first(static fn (array $row): bool => $row['gateway'] === $gateway && $row['country_id'] === null)
            ?? collect($rows)->first(static fn (array $row): bool => $row['gateway'] === $gateway);

        if (! is_array($match)) {
            return [
                'gateway' => $gateway,
                'fixed_fee_minor' => self::DEFAULT_FIXED_FEE_MINOR,
                'commission_percent' => self::DEFAULT_COMMISSION_PERCENT,
                'country_id' => $countryId,
            ];
        }

        return [
            'gateway' => $gateway,
            'fixed_fee_minor' => self::fixedFeeMinor($match['fixed_fee_minor'] ?? null),
            'commission_percent' => self::commissionPercent($match['commission_percent'] ?? null),
            'country_id' => self::countryId($match['country_id'] ?? $countryId),
        ];
    }

    private static function countryRows(iterable $countries): array
    {
        $rows = [];

        foreach ($countries as $country) {
            $id = self::countryId(is_array($country) ? ($country['id'] ?? null) : ($country->id ?? null));
            if ($id === null) {
                continue;
            }

            $rows[] = [
                'id' => $id,
                'name' => trim((string) (is_array($country) ? ($country['name'] ?? '') : ($country->name ?? ''))) ?: '—',
                'currency' => strtoupper(trim((string) (is_array($country) ? ($country['currency'] ?? '') : ($country->currency ?? '')))) ?: 'SAR',
            ];
        }

        return $rows;
    }
}
