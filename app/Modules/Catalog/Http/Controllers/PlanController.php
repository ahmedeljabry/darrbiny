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
        if ($ctr = $request->query('country_id')) $q->where('country_id', $ctr);
        if ($type = $request->query('training_type')) $q->where('training_type', $type);
        if ($hours = $request->query('hours')) $q->where('hours_count', '>=', (int) $hours);
        return response()->json(['data' => $q->orderBy('title')->paginate(20)]);
    }
}

