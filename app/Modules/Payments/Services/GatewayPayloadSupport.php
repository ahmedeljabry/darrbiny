<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRequest;
use App\Support\Vat;

final class GatewayPayloadSupport
{
    public static function setting(string $key, ?string $default = null): ?string
    {
        $value = Setting::query()->where('key', $key)->value('value');
        $value = is_string($value) ? trim($value) : null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function amount(Payment $payment): string
    {
        return number_format(((int) $payment->amount_minor) / 100, 2, '.', '');
    }

    public static function totalAmount(Payment $payment): string
    {
        return Vat::formattedGrossGatewayAmount($payment);
    }

    public static function taxAmount(Payment $payment): string
    {
        return Vat::formattedGatewayAmount($payment);
    }

    public static function orderNumber(UserRequest $request): string
    {
        return (string) ($request->order_number ?: $request->ensureOrderNumber());
    }

    public static function description(Payment $payment, UserRequest $request): string
    {
        return sprintf('Darrbiny course payment #%s', self::orderNumber($request));
    }

    public static function phone(?User $user): string
    {
        $phone = preg_replace('/\D+/', '', (string) ($user?->phone_with_cc ?? ''));

        return $phone ?: '500000000';
    }

    public static function email(?User $user): string
    {
        $email = trim((string) ($user?->email ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return sprintf('user-%s@darrbiny.local', $user?->id ?? 'guest');
    }

    public static function firstName(?User $user): string
    {
        $parts = preg_split('/\s+/', trim((string) ($user?->name ?? '')), -1, PREG_SPLIT_NO_EMPTY);

        return $parts[0] ?? 'Darrbiny';
    }

    public static function lastName(?User $user): string
    {
        $parts = preg_split('/\s+/', trim((string) ($user?->name ?? '')), -1, PREG_SPLIT_NO_EMPTY);

        return count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Customer';
    }

    public static function countryCode(UserRequest $request): string
    {
        $iso2 = strtoupper((string) ($request->country?->iso2 ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $iso2) === 1) {
            return $iso2;
        }

        return match (strtoupper((string) $request->currency)) {
            'SAR' => 'SA',
            'AED' => 'AE',
            'KWD' => 'KW',
            'JOD' => 'JO',
            default => 'SA',
        };
    }

    public static function returnUrl(string $gateway, Payment $payment, string $result): string
    {
        return url("/payments/return/{$gateway}/{$result}/{$payment->id}").'?payment_id='.$payment->id;
    }

    public static function webhookUrl(string $gateway): string
    {
        return url("/api/v1/payments/webhooks/{$gateway}");
    }
}
