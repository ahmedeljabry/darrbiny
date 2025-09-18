<?php

declare(strict_types=1);

namespace App\Modules\Training\Services;

use App\Models\TrainingDay;
use App\Models\UserRequest;
use Illuminate\Support\Facades\DB;

class TrainingDayService
{
    public function submit(UserRequest $req, string $trainerId, array $data): TrainingDay
    {
        abort_unless($req->status === UserRequest::STATUS_IN_TRAINING, 422, 'Request not in training');
        return DB::transaction(function () use ($req, $trainerId, $data) {
            $day = TrainingDay::create([
                'user_request_id' => $req->id,
                'trainer_id' => $trainerId,
                'date' => $data['date'],
                'hours_done' => (int) $data['hours_done'],
                'notes' => $data['notes'] ?? null,
                'status' => TrainingDay::STATUS_SUBMITTED,
            ]);
            return $day;
        });
    }

    public function approve(TrainingDay $day, UserRequest $req): void
    {
        $this->ensureHoursLimit($req, $day->hours_done);
        $day->status = TrainingDay::STATUS_APPROVED;
        $day->rejection_reason = null;
        $day->save();
    }

    public function reject(TrainingDay $day, string $reason): void
    {
        $day->status = TrainingDay::STATUS_REJECTED;
        $day->rejection_reason = $reason;
        $day->save();
    }

    private function ensureHoursLimit(UserRequest $req, int $additional): void
    {
        $approved = (int) \App\Models\TrainingDay::where('user_request_id', $req->id)
            ->where('status', TrainingDay::STATUS_APPROVED)
            ->sum('hours_done');
        if ($approved + $additional > $req->plan->hours_count) {
            abort(422, 'Total hours exceed plan hours');
        }
    }
}

