<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\City;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TrainerController extends BaseController
{
    public function index(Request $request)
    {
        $cityId = (string) $request->query('city_id', '');
        $search = trim((string) $request->query('q', ''));

        $q = TrainerProfile::query()
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
        $cityIds = $items->pluck('city_id')->filter()->unique()->values();
        $cityNames = $cityIds->isEmpty()
            ? collect()
            : City::whereIn('id', $cityIds)->pluck('name', 'id');

        $mapped = $items->map(function (TrainerProfile $tp) use ($cityNames) {
            $u = $tp->user;
            return [
                'id'           => $u->id,
                'name'         => $u->name,
                'rating_avg'   => $tp->rating_avg,
                'rating_count' => $tp->rating_count,
                'city_id'      => $tp->city_id,
                'city_name'    => $cityNames[$tp->city_id] ?? null,
                'country_id'   => $tp->country_id,
            ];
        });

        $paginator->setCollection($mapped);

        return response()->json(['data' => $paginator]);
    }
}

