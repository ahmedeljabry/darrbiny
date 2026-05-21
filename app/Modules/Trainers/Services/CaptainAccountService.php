<?php

declare(strict_types=1);

namespace App\Modules\Trainers\Services;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Arr;

class CaptainAccountService
{
    /** @var array<int, string> */
    private const DIRECT_UPDATE_FIELDS = [
        'country_id',
        'area_level_1',
        'area_level_2',
        'area_level_3',
        'locality',
    ];

    public function getDetails(User $user): TrainerProfile
    {
        $this->assertTrainer($user);

        $profile = $user->trainerProfile;
        if (!$profile) {
            $profile = $user->trainerProfile()->create();
        }

        $profile = $profile->fresh(['country:id,name']);

        return $this->applyPendingChangesForDisplay($profile);
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
            'area_level_1',
            'area_level_2',
            'area_level_3',
            'locality',
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

        $directUpdates = [];
        $approvalChanges = [];
        $pendingUpdates = [];

        foreach ($data as $key => $newValue) {
            $currentValue = $profile->getAttribute($key);

            if ($this->hasValueChanged($currentValue, $newValue)) {
                if (in_array($key, self::DIRECT_UPDATE_FIELDS, true)) {
                    $directUpdates[$key] = $newValue;
                    continue;
                }

                $approvalChanges[$key] = [
                    'old' => $currentValue,
                    'new' => $newValue,
                ];
                $pendingUpdates[$key] = $newValue;
            }
        }

        if (empty($directUpdates) && empty($approvalChanges)) {
            return $this->applyPendingChangesForDisplay(
                $profile->fresh(['country:id,name'])
            );
        }

        if (!empty($directUpdates)) {
            // Location updates are applied immediately without admin approval.
            $profile->fill($directUpdates);
        }

        $existingPending = is_array($profile->pending_changes) ? $profile->pending_changes : [];
        foreach (self::DIRECT_UPDATE_FIELDS as $directField) {
            unset($existingPending[$directField]);
        }

        if (!empty($approvalChanges)) {
            $profile->pending_changes = array_merge($existingPending, $pendingUpdates);
            $profile->pending_approval = true;
            $profile->pending_approval_at = now();
        } elseif ($profile->pending_approval && is_array($profile->pending_changes)) {
            if (empty($existingPending)) {
                $profile->pending_changes = null;
                $profile->pending_approval = false;
                $profile->pending_approval_at = null;
            } else {
                $profile->pending_changes = $existingPending;
            }
        }

        $profile->save();

        if (!empty($approvalChanges)) {
            // Suspend user account until approval
            $user->update(['banned_until' => now()->addYears(10)]);

            // Notify admins about pending approval
            $admins = \App\Models\User::role('ADMIN')->get();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $admins,
                    new \App\Notifications\TrainerProfileUpdateNotification($user, $profile, $approvalChanges)
                );
            }
        }

        return $this->applyPendingChangesForDisplay(
            $profile->fresh(['country:id,name'])
        );
    }

    private function assertTrainer(User $user): void
    {
        abort_unless($user->isTrainerAccount(), 403, 'Only captains can access this resource.');
    }

    private function hasValueChanged(mixed $currentValue, mixed $newValue): bool
    {
        if (is_bool($currentValue)) {
            $currentValue = (int) $currentValue;
        }
        if (is_bool($newValue)) {
            $newValue = (int) $newValue;
        }

        $currentNormalized = $currentValue ?? '';
        $newNormalized = $newValue ?? '';

        return $currentNormalized != $newNormalized;
    }

    private function applyPendingChangesForDisplay(TrainerProfile $profile): TrainerProfile
    {
        $pendingChanges = $profile->pending_changes;

        if (!$profile->pending_approval || empty($pendingChanges) || !is_array($pendingChanges)) {
            return $profile;
        }

        $profile->fill($pendingChanges);
        $profile->load(['country:id,name']);

        return $profile;
    }
}
