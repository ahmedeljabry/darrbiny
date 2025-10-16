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
        $q = request('q');
        $status = request('status');
        $countryId = request('country_id');
        $cityId = request('city_id');

        $plans = \App\Models\Plan::query()
            ->when($q, fn($qq) => $qq->where(function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhere('description','like',"%$q%");
            }))
            ->when($status === 'active', fn($qq)=>$qq->where('is_active', true))
            ->when($status === 'inactive', fn($qq)=>$qq->where('is_active', false))
            ->when($countryId, fn($qq)=>$qq->where('country_id',$countryId))
            ->when($cityId, fn($qq)=>$qq->where('city_id',$cityId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $countries = \App\Models\Country::orderBy('name')->get();
        $cities = $countryId ? \App\Models\City::where('country_id',$countryId)->orderBy('name')->get() : collect();
        return view('admin.plans.index', compact('plans','countries','cities','q','status','countryId','cityId'));
    }

    public function create()
    {
        $countries = \App\Models\Country::orderBy('name')->get();
        $cities = collect();
        return view('admin.plans.create', compact('countries','cities'));
    }

    public function store(PlanStoreRequest $request, PlansService $service)
    {
        $service->create($request->validated());
        return back()->with('status', 'تم إنشاء الخطة');
    }

    public function update(PlanUpdateRequest $request, string $id, PlansService $service)
    {
        $service->update($id, $request->validated());
        return back()->with('status', 'تم تحديث الخطة');
    }

    public function edit(string $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        $countries = \App\Models\Country::orderBy('name')->get();
        $cities = \App\Models\City::where('country_id', $plan->country_id)->orderBy('name')->get();
        return view('admin.plans.edit', compact('plan','countries','cities'));
    }

    public function destroy(string $id)
    {
        app(PlansService::class)->delete($id);
        return back()->with('status', 'تم حذف الخطة');
    }

    public function show(string $id)
    {
        return redirect()->route('admin.plans.edit', $id);
    }
}
