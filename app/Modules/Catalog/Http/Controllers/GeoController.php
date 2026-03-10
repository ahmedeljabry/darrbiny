<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class GeoController extends BaseController
{
    public function countries(Request $request)
    {
        $q = Country::query();
        return response()->json(['data' => $q->orderBy('name')->get()]);
    }
}
