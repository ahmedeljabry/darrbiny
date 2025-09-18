<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Period;
use App\Http\Requests\Admin\DashboardIndexRequest;
use App\Services\Dashboard\DashboardService;
use Illuminate\Routing\Controller as BaseController;

class DashboardController extends BaseController
{
    public function index(DashboardIndexRequest $request, DashboardService $service)
    {
        $period = Period::fromString($request->validated('period') ?? 'all');
        $metrics = $service->getMetrics($period);

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'period' => $period,
        ]);
    }
}
