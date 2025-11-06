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
            'amount' => $this->amount_minor / 100,
            'currency' => $this->currency,
            'type' => $this->type,
            'provider' => $this->provider,
            'provider_ref' => $this->provider_ref,
            'status' => $this->status,
            'app_fee_minor' => $this->app_fee_minor,
            'app_fee' => $this->app_fee_minor / 100,
            'trainer_net_minor' => $this->trainer_net_minor,
            'trainer_net' => $this->trainer_net_minor / 100,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

