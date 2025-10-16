<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PlansService
{
    public function list(): LengthAwarePaginator
    {
        return Plan::latest()->paginate(20);
    }

    public function create(array $data): Plan
    {
        $features = $data['features'] ?? null;
        unset($data['features']);

        $plan = Plan::create($data);

        if (is_array($features)) {
            $this->syncFeatures($plan, $features);
        }

        return $plan;
    }

    public function update(string $id, array $data): Plan
    {
        $plan = Plan::findOrFail($id);
        $features = $data['features'] ?? null;
        unset($data['features']);

        $plan->update($data);

        if (is_array($features)) {
            $this->syncFeatures($plan, $features);
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
}
