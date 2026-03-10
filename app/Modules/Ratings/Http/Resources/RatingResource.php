<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Http\Resources;

use App\Models\Country;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->user;
        $trainer = $this->trainer;
        if ($trainer) {
            $trainer->loadMissing([
                'trainerProfile',
                'trainerProfile.country',
            ]);
        }
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
        $areaLevelOne = $this->resolveTrainerProfileValue($trainerProfile, 'area_level_1');
        $areaLevelTwo = $this->resolveTrainerProfileValue($trainerProfile, 'area_level_2');
        $areaLevelThree = $this->resolveTrainerProfileValue($trainerProfile, 'area_level_3');
        $locality = $this->resolveTrainerProfileValue($trainerProfile, 'locality');

        $country = $trainerProfile->relationLoaded('country') ? $trainerProfile->country : null;
        if ($countryId && (!$country || $country->id !== $countryId)) {
            $country = $this->findCountry($countryId);
        }

        return [
            'country' => $country ? [
                'id' => $country->id,
                'name' => $country->name,
            ] : null,
            'country_id' => $countryId,
            'area_level_1' => $areaLevelOne,
            'area_level_2' => $areaLevelTwo,
            'area_level_3' => $areaLevelThree,
            'locality' => $locality,
            'display' => $this->formatLocation(
                $country?->name,
                $areaLevelOne,
                $areaLevelTwo,
                $areaLevelThree,
                $locality
            ),
        ];
    }

    private function getTrainerCar($trainerProfile): ?array
    {
        if (!$trainerProfile) {
            return null;
        }

        $carType = $this->resolveTrainerProfileValue($trainerProfile, 'car_type');
        $carModel = $this->resolveTrainerProfileValue($trainerProfile, 'car_model');
        $carModelYear = $this->resolveTrainerProfileValue($trainerProfile, 'car_model_year');
        $carYear = $this->resolveTrainerProfileValue($trainerProfile, 'car_year');
        $resolvedModelYear = $carModelYear ?: ($carYear !== null ? (string) $carYear : null);
        $carAvailable = $this->resolveTrainerProfileValue($trainerProfile, 'car_available');

        $carNameParts = array_filter([$carType, $carModel, $resolvedModelYear], fn ($value) => filled($value));
        $carName = !empty($carNameParts) ? implode(' ', $carNameParts) : null;

        return [
            'type' => $carType,
            'model' => $carModel,
            'year' => $carYear,
            'model_year' => $resolvedModelYear,
            'name' => $carName,
            'available' => (bool) ($carAvailable ?? false),
        ];
    }

    private function formatLocation(
        ?string $countryName,
        ?string $areaLevelOne,
        ?string $areaLevelTwo,
        ?string $areaLevelThree,
        ?string $locality
    ): string
    {
        $parts = array_filter([
            $locality,
            $areaLevelThree,
            $areaLevelTwo,
            $areaLevelOne,
            $countryName,
        ], fn ($value) => filled($value));

        return implode(', ', $parts);
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
}
