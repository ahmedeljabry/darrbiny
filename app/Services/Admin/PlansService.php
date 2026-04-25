<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class PlansService
{
    public function list(): LengthAwarePaginator
    {
        return Plan::ordered()->paginate(20);
    }

    public function create(array $data, ?UploadedFile $image = null): Plan
    {
        $features = $data['features'] ?? null;
        $schedule = $data['schedule'] ?? null;
        unset($data['features'], $data['schedule']);

        if ($image) {
            $disk = config('filesystems.default', 'public');
            $path = $image->store('plans', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
            ]);
            $data['image'] = $path;
        }

        $data['position'] = ((int) Plan::query()->max('position')) + 1;

        $plan = Plan::create($data);

        if (is_array($features)) {
            $this->syncFeatures($plan, $features);
        }

        // Sync schedule items
        if ($plan->duration_days) {
            app(\App\Services\Admin\PlanScheduleService::class)->syncScheduleItems($plan);
            
            // Update schedule titles if provided
            if (is_array($schedule)) {
                $this->updateScheduleTitles($plan, $schedule);
            }
        }

        return $plan;
    }

    public function update(string $id, array $data, ?UploadedFile $image = null): Plan
    {
        $plan = Plan::findOrFail($id);
        $oldDurationDays = $plan->duration_days;
        $features = $data['features'] ?? null;
        $schedule = $data['schedule'] ?? null;
        unset($data['features'], $data['schedule']);

        if ($image) {
            if ($plan->image) {
                Storage::disk(config('filesystems.default', 'public'))->delete($plan->image);
            }
            
            $disk = config('filesystems.default', 'public');
            $path = $image->store('plans', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
            ]);
            $data['image'] = $path;
        }

        $plan->update($data);

        if (is_array($features)) {
            $this->syncFeatures($plan, $features);
        }

        // Sync schedule items if duration_days changed
        if ($plan->duration_days != $oldDurationDays) {
            app(\App\Services\Admin\PlanScheduleService::class)->syncScheduleItems($plan);
        }
        
        // Update schedule titles if provided
        if (is_array($schedule) && $plan->duration_days) {
            $this->updateScheduleTitles($plan, $schedule);
        }

        return $plan;
    }

    public function delete(string $id): void
    {
        Plan::findOrFail($id)->delete();
    }

    private function syncFeatures(Plan $plan, array $features): void
    {
        PlanFeature::where('plan_id', $plan->id)->delete();

        $position = 0;
        foreach ($features as $label) {
            $label = trim((string) $label);
            if ($label === '') continue;
            PlanFeature::create([
                'plan_id' => $plan->id,
                'label' => $label,
                'position' => $position++,
            ]);
        }
    }

    private function updateScheduleTitles(Plan $plan, array $schedule): void
    {
        foreach ($schedule as $dayData) {
            if (!isset($dayData['day_number'])) {
                continue;
            }
            
            $dayNumber = (int) $dayData['day_number'];
            $title = $dayData['title'] ?? null;
            
            $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan->id)
                ->where('day_number', $dayNumber)
                ->first();
            
            if ($scheduleItem) {
                $scheduleItem->title = $title ? trim($title) : null;
                $scheduleItem->save();
            }
        }
    }
}
