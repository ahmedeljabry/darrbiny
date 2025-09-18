<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_with_cc' => $this->phone_with_cc,
            'currency' => $this->currency,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'points_balance' => $this->points_balance,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
        ];
    }
}

