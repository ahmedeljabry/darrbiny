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

        $plans = \App\Models\Plan::query()
            ->when($q, fn($qq) => $qq->where(function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhere('description','like',"%$q%");
            }))
            ->when($status === 'active', fn($qq)=>$qq->where('is_active', true))
            ->when($status === 'inactive', fn($qq)=>$qq->where('is_active', false))
            ->when($countryId, fn($qq)=>$qq->where('country_id',$countryId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $countries = \App\Models\Country::orderBy('name')->get();
        return view('admin.plans.index', compact('plans','countries','q','status','countryId'));
    }

    public function create()
    {
        $countries = \App\Models\Country::orderBy('name')->get();
        return view('admin.plans.create', compact('countries'));
    }

    public function store(PlanStoreRequest $request, PlansService $service)
    {
        $service->create($request->validated(), $request->file('image'));
        return back()->with('status', 'تم إنشاء الخطة');
    }

    public function update(PlanUpdateRequest $request, string $id, PlansService $service)
    {
        $service->update($id, $request->validated(), $request->file('image'));
        return back()->with('status', 'تم تحديث الخطة');
    }

    public function edit(string $id)
    {
        $plan = \App\Models\Plan::with('scheduleItems')->findOrFail($id);
        $countries = \App\Models\Country::orderBy('name')->get();
        return view('admin.plans.edit', compact('plan','countries'));
    }

    public function destroy(string $id)
    {
        app(PlansService::class)->delete($id);
        return back()->with('status', 'تم حذف الخطة');
    }

    public function show(string $id)
    {
        $plan = \App\Models\Plan::with(['scheduleItems', 'country'])->findOrFail($id);
        
        // Get all user requests for this plan
        $userRequests = \App\Models\UserRequest::with(['user', 'trainer', 'scheduleProgress'])
            ->where('plan_id', $id)
            ->latest()
            ->get();

        // Get cancelled requests
        $cancelledRequests = $userRequests->where('status', \App\Models\UserRequest::STATUS_CANCELLED);

        // Get schedule progress items for review
        $scheduleProgressItems = \App\Models\UserScheduleProgress::with(['userRequest.user', 'userRequest.trainer', 'planScheduleItem'])
            ->whereHas('userRequest', function ($q) use ($id) {
                $q->where('plan_id', $id);
            })
            ->latest()
            ->paginate(20);

        $statuses = [
            \App\Models\UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            \App\Models\UserRequest::STATUS_AWAITING_OFFERS => 'في انتظار العروض',
            \App\Models\UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            \App\Models\UserRequest::STATUS_PAID => 'مدفوع',
            \App\Models\UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            \App\Models\UserRequest::STATUS_COMPLETED => 'مكتمل',
            \App\Models\UserRequest::STATUS_CANCELLED => 'ملغي',
        ];

        return view('admin.plans.show', compact('plan', 'userRequests', 'cancelledRequests', 'scheduleProgressItems', 'statuses'));
    }

    public function updateRequestStatus(\Illuminate\Http\Request $request, string $planId, string $requestId)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                \App\Models\UserRequest::STATUS_PENDING_PAYMENT,
                \App\Models\UserRequest::STATUS_AWAITING_OFFERS,
                \App\Models\UserRequest::STATUS_OFFER_SELECTED,
                \App\Models\UserRequest::STATUS_PAID,
                \App\Models\UserRequest::STATUS_IN_TRAINING,
                \App\Models\UserRequest::STATUS_COMPLETED,
                \App\Models\UserRequest::STATUS_CANCELLED,
            ])],
        ]);

        $userRequest = \App\Models\UserRequest::where('plan_id', $planId)
            ->findOrFail($requestId);

        $oldStatus = $userRequest->status;
        $userRequest->status = $validated['status'];
        $userRequest->save();

        return back()->with('status', "تم تحديث حالة الطلب من {$oldStatus} إلى {$validated['status']}");
    }
}
