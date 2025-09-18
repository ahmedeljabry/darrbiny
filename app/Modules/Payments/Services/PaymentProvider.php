<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;

interface PaymentProvider
{
    public function initiate(Payment $payment, array $metadata = []): array;
    public function validateWebhook(array $payload, array $headers = []): bool;
}

