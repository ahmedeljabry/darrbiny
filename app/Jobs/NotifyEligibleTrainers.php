<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Models\User;
use App\Notifications\NewRequestAvailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyEligibleTrainers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly UserRequest $request) {}

    public function handle(): void
    {
        $plan = $this->request->plan;

        $query = TrainerProfile::query()
            ->whereNotNull('verified_at')
            ->when($plan?->city_id, fn($q) => $q->where('city_id', $plan->city_id))
            ->when(!$plan?->city_id && $plan?->country_id, fn($q) => $q->where('country_id', $plan->country_id))
            ->with('user');

        $query->chunk(200, function ($profiles) {
            $trainers = $profiles->map(fn($p) => $p->user)->filter();
            Notification::send($trainers, new NewRequestAvailable($this->request));
        });
    }
}
