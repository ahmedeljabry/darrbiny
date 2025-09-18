<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;

class DummyProvider implements PaymentProvider
{
    public function initiate(Payment $payment, array $metadata = []): array
    {
        return [
            'checkout_url' => url('/dummy-checkout/'.$payment->id),
        ];
    }

    public function validateWebhook(array $payload, array $headers = []): bool
    {
        return true; // Always valid in dummy
    }
}

