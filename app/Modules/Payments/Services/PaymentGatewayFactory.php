<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function __construct(
        private readonly TapProvider $tap,
        private readonly TabbyProvider $tabby,
        private readonly TamaraProvider $tamara,
    ) {}

    public function forMethod(string $paymentMethod): PaymentProvider
    {
        return match ($paymentMethod) {
            Payment::METHOD_TAP => $this->tap,
            Payment::METHOD_TABBY => $this->tabby,
            Payment::METHOD_TAMARA => $this->tamara,
            default => throw new InvalidArgumentException("Unsupported gateway payment method [{$paymentMethod}]."),
        };
    }
}
