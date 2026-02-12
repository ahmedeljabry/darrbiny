<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Http\Resources;

use App\Models\City;
use App\Models\Country;
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

            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'profile_picture' => $user->profile_picture_url,
            ] : null,

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

        $countryId = $this->resolveTrainerProfileValue($trainerProfile, 'country_id');
        $cityId = $this->resolveTrainerProfileValue($trainerProfile, 'city_id');

        $country = $trainerProfile->relationLoaded('country') ? $trainerProfile->country : null;
        if ($countryId && (!$country || $country->id !== $countryId)) {
            $country = $this->findCountry($countryId);
        }

        $city = $trainerProfile->relationLoaded('city') ? $trainerProfile->city : null;
        if ($cityId && (!$city || $city->id !== $cityId)) {
            $city = $this->findCity($cityId);
        }

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

        $carType = $this->resolveTrainerProfileValue($trainerProfile, 'car_type');
        $carModelYear = $this->resolveTrainerProfileValue($trainerProfile, 'car_model_year');
        $carAvailable = $this->resolveTrainerProfileValue($trainerProfile, 'car_available');

        $carName = null;
        if ($carType) {
            $carName = $carType;
            if ($carModelYear) {
                $carName .= ' ' . $carModelYear;
            }
        }

        return [
            'type' => $carType,
            'model_year' => $carModelYear,
            'name' => $carName,
            'available' => (bool) ($carAvailable ?? false),
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

    private function resolveTrainerProfileValue($trainerProfile, string $field)
    {
        if (!$trainerProfile) {
            return null;
        }

        $value = $trainerProfile->getAttribute($field);
        if (
            $trainerProfile->pending_approval
            && is_array($trainerProfile->pending_changes)
            && array_key_exists($field, $trainerProfile->pending_changes)
        ) {
            $value = $trainerProfile->pending_changes[$field];
        }

        return $value;
    }

    private function findCountry(string $id): ?Country
    {
        static $cache = [];
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        $cache[$id] = Country::find($id);
        return $cache[$id];
    }

    private function findCity(string $id): ?City
    {
        static $cache = [];
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        $cache[$id] = City::find($id);
        return $cache[$id];
    }
}



