<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\Setting;

class TapProvider implements PaymentProvider
{
    protected function cfg(string $key, ?string $default = null): ?string
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }

    public function initiate(Payment $payment, array $metadata = []): array
    {
        $secret = $this->cfg('payment.tap.secret_key');
        $public = $this->cfg('payment.tap.public_key');
        if (!$secret || !$public) {
            throw new \RuntimeException('Tap keys not configured');
        }

        // Here you would call Tap API to create a charge/session and get a redirect URL
        // For now, return a placeholder with required info to proceed on frontend
        return [
            'provider' => 'tap',
            'amount' => $payment->amount_minor / 100,
            'currency' => $payment->currency,
            'reference' => $payment->id,
            'public_key' => $public,
            'checkout_url' => url('/payments/redirect/tap/'.$payment->id),
        ];
    }

    public function validateWebhook(array $payload, array $headers = []): bool
    {
        $secret = $this->cfg('payment.tap.webhook_secret');
        if (!$secret) return false;
        // Many gateways send a signature header. Assuming 'tap-signature' exists
        $sigHeader = $headers['tap-signature'][0] ?? $headers['Tap-Signature'][0] ?? null;
        if (!$sigHeader) return false;
        $computed = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
        // Timing-safe compare
        return hash_equals($computed, $sigHeader);
    }
}

