<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'trainer_id' => $this->trainer_id,
            'plan_id' => $this->plan_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'description' => $this->description,
            'has_user_car' => $this->has_user_car,
            'wants_trainer_car' => $this->wants_trainer_car,
            'needs_pickup' => $this->needs_pickup,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'currency' => $this->currency,
            'app_fee_reserved_minor' => $this->app_fee_reserved_minor,
            'total_paid_minor' => $this->total_paid_minor,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone_with_cc' => $this->user->phone_with_cc,
                'profile_picture' => $this->user->profile_picture_url ?? null,
            ]),
            'trainer' => $this->whenLoaded('trainer', fn () => [
                'id' => $this->trainer->id,
                'name' => $this->trainer->name,
                'profile_picture' => $this->trainer->profile_picture_url ?? null,
            ]),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'title' => $this->plan->title,
                'description' => $this->plan->description,
                'hours_count' => $this->plan->hours_count,
                'session_count' => $this->plan->session_count,
                'price_min' => $this->plan->price_min,
                'country' => $this->whenLoaded('plan.country', fn () => [
                    'id' => $this->plan->country->id,
                    'name' => $this->plan->country->name,
                ]),
                'city' => $this->whenLoaded('plan.city', fn () => [
                    'id' => $this->plan->city->id,
                    'name' => $this->plan->city->name,
                ]),
            ]),
            'offers' => $this->whenLoaded('offers', fn () => $this->offers->map(fn ($offer) => [
                'id' => $offer->id,
                'trainer_id' => $offer->trainer_id,
                'price_minor' => $offer->price_minor,
                'message' => $offer->message,
                'status' => $offer->status,
                'trainer' => $offer->relationLoaded('trainer') ? [
                    'id' => $offer->trainer->id,
                    'name' => $offer->trainer->name,
                ] : null,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'type' => $payment->type,
                'status' => $payment->status,
                'created_at' => $payment->created_at?->toIso8601String(),
            ])),
            'training_days' => $this->whenLoaded('trainingDays', fn () => $this->trainingDays->map(fn ($day) => [
                'id' => $day->id,
                'date' => $day->date?->format('Y-m-d'),
                'hours_done' => $day->hours_done,
                'status' => $day->status,
                'notes' => $day->notes,
            ])),
            'rates' => $this->when(
                $this->relationLoaded('user') && $this->user->relationLoaded('rates'),
                fn () => $this->user->rates
                    ->where('user_request_id', $this->id)
                    ->map(fn ($rate) => [
                        'id' => $rate->id,
                        'user_id' => $rate->user_id,
                        'trainer_id' => $rate->trainer_id,
                        'stars' => $rate->stars,
                        'comment' => $rate->comment,
                        'created_at' => $rate->created_at?->toIso8601String(),
                    ])->values()
            ),
        ];
    }

}
