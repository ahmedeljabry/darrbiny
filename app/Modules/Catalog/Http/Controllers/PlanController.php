<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PlanController extends BaseController
{
    public function index(Request $request)
    {
        $q = Plan::query()->where('is_active', true);
        if ($cty = $request->query('city_id')) $q->where('city_id', $cty);
        return response()->json(['data' => $q->paginate(20)]);
    }

    public function show(Plan $plan)
    {
        $plan->load(['features:id,label,plan_id', 'city:id,name', 'country:id,name']);
        return response()->json(['data' => $plan]);
    }
}
