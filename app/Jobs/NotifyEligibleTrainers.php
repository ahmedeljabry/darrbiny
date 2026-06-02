<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Notifications\NewRequestAvailable;
use App\Support\TrainerLocationMatcher;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class NotifyEligibleTrainers
{
    use Dispatchable;

    public function __construct(private readonly UserRequest $request) {}

    public function handle(): void
    {
        $this->request->refresh();

        if ($this->request->status !== UserRequest::STATUS_AWAITING_OFFERS) {
            return;
        }

        $this->request->loadMissing(['plan', 'user']);

        $query = TrainerLocationMatcher::applyEligibleTrainerProfilesScope(
            TrainerProfile::query()->with('user'),
            $this->request
        );

        $query->chunk(200, function ($profiles) {
            $trainers = $profiles->map(fn($p) => $p->user)->filter();
            Notification::send($trainers, new NewRequestAvailable($this->request));
        });
    }
}
