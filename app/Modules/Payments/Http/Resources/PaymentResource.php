<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_request_id' => $this->user_request_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'type' => $this->type,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'gateway_reference' => $this->gateway_reference,
            'gateway_status' => $this->gateway_status,
            'checkout_url' => $this->gateway_checkout_url,
            'gateway' => [
                'reference' => $this->gateway_reference,
                'status' => $this->gateway_status,
                'checkout_url' => $this->gateway_checkout_url,
            ],
            'app_fee' => $this->app_fee_minor,
            'trainer_net' => $this->trainer_net_minor,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
