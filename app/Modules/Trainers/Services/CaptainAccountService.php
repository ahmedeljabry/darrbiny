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
            'car_model',
            'car_model_year',
            'car_year',
            'car_plate_number',
            'has_driving_license',
            'license_number',
            'license_expiry_date',
        ]);

        foreach (['car_available', 'pickup_available'] as $optionalBool) {
            if (!array_key_exists($optionalBool, $payload) || $payload[$optionalBool] === null) {
                unset($data[$optionalBool]);
            }
        }

        // Check if there are actual changes
        $hasChanges = false;
        $changes = [];
        
        foreach ($data as $key => $newValue) {
            $currentValue = $profile->getAttribute($key);
            
            // Normalize values for comparison
            if (is_bool($currentValue)) {
                $currentValue = (int) $currentValue;
            }
            if (is_bool($newValue)) {
                $newValue = (int) $newValue;
            }
            
            // Compare values (handle null cases)
            $currentNormalized = $currentValue ?? '';
            $newNormalized = $newValue ?? '';
            
            if ($currentNormalized != $newNormalized) {
                $hasChanges = true;
                $changes[$key] = [
                    'old' => $currentValue,
                    'new' => $newValue,
                ];
            }
        }

        // Only set pending approval if there are actual changes
        if ($hasChanges) {
            // Store pending changes (only new values)
            $pendingChanges = [];
            foreach ($data as $key => $value) {
                $pendingChanges[$key] = $value;
            }
            
            $profile->pending_changes = $pendingChanges;
            $profile->pending_approval = true;
            $profile->pending_approval_at = now();
            $profile->save();

            // Suspend user account until approval
            $user->update(['banned_until' => now()->addYears(10)]); // Temporary ban until approval

            // Notify admins about pending approval
            $admins = \App\Models\User::role('ADMIN')->get();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $admins,
                    new \App\Notifications\TrainerProfileUpdateNotification($user, $profile, $changes)
                );
            }
        } else {
            // No changes, just return the profile without modification
            return $profile->fresh(['country:id,name', 'city:id,name']);
        }

        return $profile->fresh(['country:id,name', 'city:id,name']);
    }

    private function assertTrainer(User $user): void
    {
        abort_unless($user->hasRole('TRAINER'), 403, 'Only captains can access this resource.');
    }
}
