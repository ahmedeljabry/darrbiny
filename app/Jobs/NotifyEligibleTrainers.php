<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
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
        $this->request->loadMissing(['plan', 'user']);

        $query = TrainerProfile::query()
            ->whereNotNull('verified_at')
            ->when($this->request->country_id, fn($q) => $q->where('country_id', $this->request->country_id))
            ->when($this->request->area_level_1, fn($q) => $q->where('area_level_1', $this->request->area_level_1))
            ->when($this->request->area_level_2, fn($q) => $q->where('area_level_2', $this->request->area_level_2))
            ->when($this->request->area_level_3, fn($q) => $q->where('area_level_3', $this->request->area_level_3))
            ->with('user');

        $query->chunk(200, function ($profiles) {
            $trainers = $profiles->map(fn($p) => $p->user)->filter();
            Notification::send($trainers, new NewRequestAvailable($this->request));
        });
    }
}
