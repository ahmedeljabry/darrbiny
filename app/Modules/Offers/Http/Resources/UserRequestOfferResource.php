<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRequestOfferResource extends JsonResource
{
    public function toArray($request): array
    {
        $userRequest = $this->whenLoaded('userRequest');
        $plan = $userRequest?->plan;
        $trainer = $this->trainer;
        $trainerProfile = $trainer?->trainerProfile;

        $durationDays = (int) ($plan?->duration_days ?? 0);
        $endDate = $userRequest?->start_date && $durationDays > 0
            ? $userRequest->start_date->copy()->addDays($durationDays)
            : null;

        $priceMinor = (int) ($this->price_minor ?? 0);
        $price = $priceMinor / 100;
        $currency = $userRequest?->currency;

        return [
            'id' => $this->id,
            'user_request_id' => $this->user_request_id,
            'status' => $this->status,
            'message' => $this->message,
            'price' => [
                'amount' => $price,
                'minor' => $priceMinor,
                'currency' => $currency,
                'display' => $currency ? number_format($price, 0) . ' ' . $currency : null,
            ],
            'request' => $userRequest ? [
                'id' => $userRequest->id,
                'start_date' => $userRequest->start_date?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'duration' => [
                    'days' => $durationDays,
                    'hours' => (int) ($plan?->hours_count ?? 0),
                ],
                'needs_pickup' => (bool) $userRequest->needs_pickup,
                'description' => $userRequest->description,
            ] : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'title' => $plan->title,
                'duration_days' => (int) ($plan->duration_days ?? 0),
                'hours_count' => (int) ($plan->hours_count ?? 0),
                'country' => $plan->country ? [
                    'id' => $plan->country->id,
                    'name' => $plan->country->name,
                ] : null,
                'city' => $plan->city ? [
                    'id' => $plan->city->id,
                    'name' => $plan->city->name,
                ] : null,
            ] : null,
            'trainer' => $trainer ? [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'profile_picture' => $trainer->profile_picture_url ?? null,
                'rating' => [
                    'average' => (float) ($trainerProfile->rating_avg ?? 0),
                    'count' => (int) ($trainerProfile->rating_count ?? 0),
                ],
                'car' => [
                    'available' => (bool) ($trainerProfile->car_available ?? false),
                    'model' => $this->trainerCarModel($trainerProfile),
                ],
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function trainerCarModel($trainerProfile): ?string
    {
        if (!$trainerProfile || !$trainerProfile->car_available) {
            return null;
        }

        $parts = array_filter([
            $trainerProfile->car_type,
            $trainerProfile->car_model_year,
        ], static fn ($value) => filled($value));

        $model = trim(implode(' ', $parts));

        return $model !== '' ? $model : 'Trainer car';
    }
}
