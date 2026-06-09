<?php

declare(strict_types=1);

namespace App\Modules\Requests\Services;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Support\TrainerLocationMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class TrainerAvailabilityService
{
    private const CACHE_TTL_SECONDS = 60;

    public function hasEligibleTrainerForLocation(array $location): bool
    {
        $requestLocation = new UserRequest($location);

        return (bool) Cache::remember(
            $this->cacheKey($requestLocation),
            self::CACHE_TTL_SECONDS,
            fn (): bool => $this->eligibleTrainerQuery($requestLocation)->exists()
        );
    }

    public function eligibleTrainerQuery(UserRequest $requestLocation): Builder
    {
        $query = TrainerProfile::query()
            ->select('trainer_profiles.id')
            ->whereNull('trainer_profiles.deleted_at')
            ->whereHas('user', function (Builder $query): void {
                $query
                    ->trainerAccount()
                    ->whereNull('deleted_at')
                    ->where(function (Builder $banQuery): void {
                        $banQuery
                            ->whereNull('banned_until')
                            ->orWhere('banned_until', '<=', now());
                    });
            });

        return TrainerLocationMatcher::applyEligibleTrainerProfilesScope($query, $requestLocation);
    }

    private function cacheKey(UserRequest $requestLocation): string
    {
        $parts = collect([
            $requestLocation->country_id,
            $requestLocation->area_level_1,
            $requestLocation->area_level_2,
            $requestLocation->area_level_3,
        ])
            ->map(fn (mixed $value): string => TrainerLocationMatcher::normalize($value) ?? '-')
            ->implode('|');

        return 'trainer-availability:'.sha1($parts);
    }
}
