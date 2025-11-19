<?php

declare(strict_types=1);

namespace App\Modules\Trainers\Services;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Arr;

class CaptainAccountService
{
    public function getDetails(User $user): TrainerProfile
    {
        $this->assertTrainer($user);

        $profile = $user->trainerProfile;
        if (!$profile) {
            $profile = $user->trainerProfile()->create();
        }

        return $profile->fresh(['country:id,name', 'city:id,name']);
    }

    public function upsert(User $user, array $payload): TrainerProfile
    {
        $this->assertTrainer($user);

        $profile = $user->trainerProfile;
        if (!$profile) {
            $profile = $user->trainerProfile()->create();
        }

        $data = Arr::only($payload, [
            'bio',
            'country_id',
            'city_id',
            'car_available',
            'pickup_available',
            'car_type',
            'car_model_year',
            'has_driving_license',
        ]);

        foreach (['car_available', 'pickup_available'] as $optionalBool) {
            if (!array_key_exists($optionalBool, $payload) || $payload[$optionalBool] === null) {
                unset($data[$optionalBool]);
            }
        }

        $profile->fill($data)->save();

        $location = Arr::only($payload, ['country_id', 'city_id']);
        if (!empty(array_filter($location, static fn ($value) => $value !== null))) {
            $user->fill(array_filter($location, static fn ($value) => $value !== null));
            $user->save();
        }

        return $profile->fresh(['country:id,name', 'city:id,name']);
    }

    private function assertTrainer(User $user): void
    {
        abort_unless($user->hasRole('TRAINER'), 403, 'Only captains can access this resource.');
    }
}
