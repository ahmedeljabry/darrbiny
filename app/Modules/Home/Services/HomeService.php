<?php

namespace App\Modules\Home\Services;

use App\Models\Plan;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class HomeService
{
    public function getHomeData(array $includes = [], int $limitPlans = 5, int $limitTrainers = 5, int $countryId = 0, int $cityId = 0): array
    {
        $cacheKey = $this->generateCacheKey($includes, $limitPlans, $limitTrainers, $countryId, $cityId);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($includes, $limitPlans, $limitTrainers, $countryId, $cityId) {
            return [
                'plans' => $this->getPlans($includes, $limitPlans, $countryId, $cityId),
                'trainers' => $this->getTrainers($includes, $limitTrainers, $countryId, $cityId),
            ];
        });
    }
    protected function generateCacheKey(array $includes, int $limitPlans, int $limitTrainers, int $countryId, int $cityId): string
    {
        $includesKey = implode(',', Arr::sort($includes));
        return "home_data:includes={$includesKey}:limit_plans={$limitPlans}:limit_trainers={$limitTrainers}:country_id={$countryId}:city_id={$cityId}";

    }

    
