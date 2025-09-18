<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PlanStoreRequest;
use App\Http\Requests\Admin\PlanUpdateRequest;
use App\Services\Admin\PlansService;
use Illuminate\Routing\Controller as BaseController;

class PlansController extends BaseController
{
    public function index(PlansService $service)
    {
        $plans = $service->list();
        return view('admin.plans.index', compact('plans'));
    }

    public function store(PlanStoreRequest $request, PlansService $service)
    {
        $service->create($request->validated());
        return back()->with('status', 'Plan created');
    }

    public function update(PlanUpdateRequest $request, string $id, PlansService $service)
    {
        $service->update($id, $request->validated());
        return back()->with('status', 'Plan updated');
    }

    public function destroy(string $id)
    {
        app(PlansService::class)->delete($id);
        return back()->with('status', 'Plan deleted');
    }
}
