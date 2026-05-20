<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\Country;
use App\Models\TrainerProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TrainerController extends BaseController
{
    public function index(Request $request)
    {
        $countryId = (string) $request->query('country_id', '');
        $search = trim((string) $request->query('name', $request->query('q', '')));

        $q = TrainerProfile::query()
            ->where('pending_approval', false)
            ->when($countryId !== '', fn($qq) => $qq->where('country_id', $countryId))
            ->whereHas('user', function ($uq) use ($search) {
                $uq->whereNull('deleted_at')
                    ->where(function ($w) {
                        $w->whereNull('banned_until')
                            ->orWhere('banned_until', '<=', now());
                    })
                    ->trainerAccount();
                if ($search !== '') {
                    $uq->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            })
            ->with('user:id,name,deleted_at,profile_picture_id', 'user.profilePicture')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count');

        $paginator = $q->paginate(20);

        $items = collect($paginator->items());

        $countryIds = $items->pluck('country_id')->filter()->unique()->values();
        $countryNames = $countryIds->isEmpty()
            ? collect()
            : Country::whereIn('id', $countryIds)->pluck('name', 'id');

        $mapped = $items->map(function (TrainerProfile $tp) use ($countryNames) {
            $u = $tp->user;
            return [
                'id'              => $u->id,
                'name'            => $u->name,
                'profile_picture' => $u->profile_picture_url,
                'rating_avg'      => $tp->rating_avg,
                'rating_count'    => $tp->rating_count,
                'country_id'      => $tp->country_id,
                'country_name'    => $countryNames[$tp->country_id] ?? null,
                'area_level_1'    => $tp->area_level_1,
                'area_level_2'    => $tp->area_level_2,
                'area_level_3'    => $tp->area_level_3,
                'locality'        => $tp->locality,
            ];
        });

        $paginator->setCollection($mapped);

        return response()->json(['data' => $paginator]);
    }
}
