<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\PlanScheduleItem;
use Illuminate\Support\Facades\DB;

class PlanScheduleService
{
    /**
     * Sync schedule items based on plan duration_days
     * Creates items for days that don't exist, removes excess items
     */
    public function syncScheduleItems(Plan $plan): void
    {
        $durationDays = (int) ($plan->duration_days ?? 0);
        
        if ($durationDays <= 0) {
            // Remove all schedule items if duration is 0 or invalid
            PlanScheduleItem::where('plan_id', $plan->id)->delete();
            return;
        }

        DB::transaction(function () use ($plan, $durationDays) {
            $existingItems = PlanScheduleItem::where('plan_id', $plan->id)
                ->orderBy('day_number')
                ->get()
                ->keyBy('day_number');

            // Create or update items for each day
            for ($day = 1; $day <= $durationDays; $day++) {
                if (isset($existingItems[$day])) {
                    // Update existing item position
                    $existingItems[$day]->position = $day;
                    $existingItems[$day]->save();
                } else {
                    // Create new item
                    PlanScheduleItem::create([
                        'plan_id' => $plan->id,
                        'day_number' => $day,
                        'title' => null,
                        'position' => $day,
                    ]);
                }
            }

            // Delete items beyond duration_days
            PlanScheduleItem::where('plan_id', $plan->id)
                ->where('day_number', '>', $durationDays)
                ->delete();
        });
    }

    /**
     * Initialize user schedule progress when user subscribes
     * Creates progress records for all schedule items in the plan
     */
    public function initializeUserSchedule(\App\Models\UserRequest $userRequest): void
    {
        $plan = $userRequest->plan;
        $scheduleItems = PlanScheduleItem::where('plan_id', $plan->id)
            ->ordered()
            ->get();

        if ($scheduleItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($userRequest, $scheduleItems) {
            foreach ($scheduleItems as $item) {
                \App\Models\UserScheduleProgress::create([
                    'user_request_id' => $userRequest->id,
                    'plan_schedule_item_id' => $item->id,
                    'day_number' => $item->day_number,
                    'is_checked' => false,
                ]);
            }
        });
    }

    /**
     * Update schedule item title
     */
    public function updateItem(string $id, array $data): PlanScheduleItem
    {
        $item = PlanScheduleItem::findOrFail($id);
        $item->update($data);
        return $item;
    }

    /**
     * Delete schedule item
     */
    public function deleteItem(string $id): void
    {
        $item = PlanScheduleItem::findOrFail($id);
        $item->delete();
    }
}

