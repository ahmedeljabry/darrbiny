<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class GeoAdminController extends BaseController
{
    public function index()
    {
        $countries = Country::orderBy('name')->get();
        $cities = City::with('country')->orderBy('name')->paginate(50);
        return view('admin.geo.index', compact('countries','cities'));
    }
}

