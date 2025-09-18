<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PlansService
{
    public function list(): LengthAwarePaginator
    {
        return Plan::latest()->paginate(20);
    }

    public function create(array $data): Plan
    {
        return Plan::create($data);
    }

    public function update(string $id, array $data): Plan
    {
        $plan = Plan::findOrFail($id);
        $plan->update($data);
        return $plan;
    }

    public function delete(string $id): void
    {
        Plan::findOrFail($id)->delete();
    }
}

