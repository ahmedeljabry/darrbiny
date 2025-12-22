<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->user;
        $trainer = $this->trainer;
        $trainerProfile = $trainer?->trainerProfile;

        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // معلومات الطالب الذي عمل التقييم
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'profile_picture' => $user->profile_picture_url,
            ] : null,

            // معلومات الكابتن (المدرب)
            'trainer' => $trainer ? [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'profile_picture' => $trainer->profile_picture_url,
                'location' => $this->getTrainerLocation($trainerProfile),
                'car' => $this->getTrainerCar($trainerProfile),
            ] : null,
        ];
    }

    private function getTrainerLocation($trainerProfile): ?array
    {
        if (!$trainerProfile) {
            return null;
        }

        $country = $trainerProfile->country;
        $city = $trainerProfile->city;

        return [
            'country' => $country ? [
                'id' => $country->id,
                'name' => $country->name,
            ] : null,
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->name,
            ] : null,
            'display' => $this->formatLocation($country, $city),
        ];
    }

    private function getTrainerCar($trainerProfile): ?array
    {
        if (!$trainerProfile) {
            return null;
        }

        $carName = null;
        if ($trainerProfile->car_type) {
            $carName = $trainerProfile->car_type;
            if ($trainerProfile->car_model_year) {
                $carName .= ' ' . $trainerProfile->car_model_year;
            }
        }

        return [
            'type' => $trainerProfile->car_type,
            'model_year' => $trainerProfile->car_model_year,
            'name' => $carName,
            'available' => $trainerProfile->car_available ?? false,
        ];
    }

    private function formatLocation($country, $city): string
    {
        $parts = [];
        if ($city) {
            $parts[] = $city->name;
        }
        if ($country) {
            $parts[] = $country->name;
        }
        return implode(', ', $parts) ?: '';
    }
}

