<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\DashboardMetrics;
use App\Enums\Period;
use App\Models\City;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class DashboardService
{
    public function getMetrics(Period $period): DashboardMetrics
    {
        $cacheKey = 'dashboard:metrics:'.$period->value;

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($period) {
            $since = $period->range();

            $usersTotal = User::count();
            $plansTotal = Plan::count();
            $citiesTotal = City::count();
            $countriesTotal = Country::count();

            $usersNew = $since ? User::where('created_at', '>=', $since)->count() : 0;
            $plansNew = $since ? Plan::where('created_at', '>=', $since)->count() : 0;
            $citiesNew = $since ? City::where('created_at', '>=', $since)->count() : 0;
            $countriesNew = $since ? Country::where('created_at', '>=', $since)->count() : 0;

            return new DashboardMetrics(
                usersTotal: $usersTotal,
                usersNew: $usersNew,
                plansTotal: $plansTotal,
                plansNew: $plansNew,
                citiesTotal: $citiesTotal,
                citiesNew: $citiesNew,
                countriesTotal: $countriesTotal,
                countriesNew: $countriesNew,
            );
        });
    }
}

