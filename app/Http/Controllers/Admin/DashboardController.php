<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    public function __invoke()
    {
        $planCount = \App\Models\Plan::count();
        $countriesCount = \App\Models\Country::count();
        $citiesCount = \App\Models\City::count();
        $usersCount = \App\Models\User::count();

        $labels = [];
        $userSeries = [];
        $planSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('Y-m-d');
            $userSeries[] = \App\Models\User::whereDate('created_at', $day)->count();
            $planSeries[] = \App\Models\Plan::whereDate('created_at', $day)->count();
        }

        return view('admin.dashboard', compact(
            'planCount','countriesCount','citiesCount','usersCount','labels','userSeries','planSeries'
        ));
    }

}
