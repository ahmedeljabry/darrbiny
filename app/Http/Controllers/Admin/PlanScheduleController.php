<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Services\Admin\PlanScheduleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PlanScheduleController extends BaseController
{
    public function __construct(private readonly PlanScheduleService $service) {}

    public function index(string $planId)
    {
        $plan = Plan::findOrFail($planId);
        $scheduleItems = \App\Models\PlanScheduleItem::where('plan_id', $plan->id)
            ->ordered()
            ->get();

        return view('admin.plans.schedule', compact('plan', 'scheduleItems'));
    }

    public function store(Request $request, string $planId)
    {
        $plan = Plan::findOrFail($planId);
        
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.day_number' => ['required', 'integer', 'min:1'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->syncScheduleItems($plan);
        foreach ($validated['items'] as $itemData) {
            $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan->id)
                ->where('day_number', $itemData['day_number'])
                ->first();

            if ($scheduleItem) {
                $scheduleItem->title = $itemData['title'] ?? null;
                $scheduleItem->save();
            }
        }

        return back()->with('status', 'تم حفظ جدول المتابعة بنجاح');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->updateItem($id, $validated);
        return back()->with('status', 'تم تحديث العنصر بنجاح');
    }

    public function destroy(string $id)
    {
        $this->service->deleteItem($id);
        return back()->with('status', 'تم حذف العنصر بنجاح');
    }
}

