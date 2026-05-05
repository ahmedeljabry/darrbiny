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
            'email' => $this->email,
            'phone_with_cc' => $this->phone_with_cc,
            'currency' => $this->currency,
            'country_id' => $this->country_id,
            'profile_picture_url' => $this->profile_picture_url,
            'canChangePic' => (bool) ($this->can_change_picture ?? true),
            'points_balance' => $this->points_balance,
            'referral_code' => $this->referral_code,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'latest_transactions' => \App\Models\WalletTransaction::where('user_id', $this->id)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ];
                }),
        ];
    }
}
