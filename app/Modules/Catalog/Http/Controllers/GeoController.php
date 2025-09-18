<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class GeoController extends BaseController
{
    public function countries()
    {
        return response()->json(['data' => Country::query()->orderBy('name')->get()]);
    }

    public function cities(Request $request)
    {
        $countryId = $request->query('country_id');
        $q = City::query();
        if ($countryId) $q->where('country_id', $countryId);
        return response()->json(['data' => $q->orderBy('name')->get()]);
    }
}

