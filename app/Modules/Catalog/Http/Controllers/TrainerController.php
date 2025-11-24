<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TrainerController extends BaseController
{
    public function index(Request $request)
    {
        $countryId = (string) $request->query('country_id', '');
        $cityId = (string) $request->query('city_id', '');
        $search = trim((string) $request->query('name', $request->query('q', '')));

        $q = TrainerProfile::query()
            ->when($countryId !== '', fn($qq) => $qq->where('country_id', $countryId))
            ->when($cityId !== '', fn($qq) => $qq->where('city_id', $cityId))
            ->whereHas('user', function ($uq) use ($search) {
                $uq->whereNull('deleted_at')->role('TRAINER');
                if ($search !== '') {
                    $uq->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            })
            ->with('user:id,name,deleted_at')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count');

        $paginator = $q->paginate(20);

        $items = collect($paginator->items());
        
        // Load city names
        $cityIds = $items->pluck('city_id')->filter()->unique()->values();
        $cityNames = $cityIds->isEmpty()
            ? collect()
            : City::whereIn('id', $cityIds)->pluck('name', 'id');

        // Load country names
        $countryIds = $items->pluck('country_id')->filter()->unique()->values();
        $countryNames = $countryIds->isEmpty()
            ? collect()
            : Country::whereIn('id', $countryIds)->pluck('name', 'id');

        $mapped = $items->map(function (TrainerProfile $tp) use ($cityNames, $countryNames) {
            $u = $tp->user;
            return [
                'id'           => $u->id,
                'name'         => $u->name,
                'rating_avg'   => $tp->rating_avg,
                'rating_count' => $tp->rating_count,
                'city_id'      => $tp->city_id,
                'city_name'    => $cityNames[$tp->city_id] ?? null,
                'country_id'   => $tp->country_id,
                'country_name' => $countryNames[$tp->country_id] ?? null,
            ];
        });

        $paginator->setCollection($mapped);

        return response()->json(['data' => $paginator]);
    }
}

