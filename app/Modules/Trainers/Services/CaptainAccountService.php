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

        // Store current values as pending changes and suspend account
        $currentData = $profile->only(array_keys($data));
        $pendingChanges = array_merge($currentData, $data);
        
        $profile->pending_changes = $pendingChanges;
        $profile->pending_approval = true;
        $profile->pending_approval_at = now();
        $profile->save();

        // Suspend user account until approval
        $user->update(['banned_until' => now()->addYears(10)]); // Temporary ban until approval

        return $profile->fresh(['country:id,name', 'city:id,name']);
    }

    private function assertTrainer(User $user): void
    {
        abort_unless($user->hasRole('TRAINER'), 403, 'Only captains can access this resource.');
    }
}
