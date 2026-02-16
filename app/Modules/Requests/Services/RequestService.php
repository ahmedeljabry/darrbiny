<?php

declare(strict_types=1);

namespace App\Modules\Requests\Services;

use App\Models\Plan;
use App\Models\TrainerOffer;
use App\Models\UserRequest;
use Illuminate\Support\Facades\DB;
use App\Jobs\NotifyEligibleTrainers;
use App\Services\ConversationService;

class RequestService
{
    public function create(array $data, string $userId): UserRequest
    {
        $plan = Plan::findOrFail($data['plan_id']);

        return DB::transaction(function () use ($data, $userId, $plan) {
            $freeRetrySource = $this->findEligibleFreeRetrySource(
                $userId,
                (string) $plan->id,
                $data['trainer_id'] ?? null
            );

            $req = new UserRequest($data);
            $req->user_id = $userId;
            $req->currency = auth()->user()?->currency ?? 'USD';
            $req->status = $freeRetrySource ? UserRequest::STATUS_IN_TRAINING : UserRequest::STATUS_PENDING_PAYMENT;
            $req->app_fee_reserved_minor = $freeRetrySource ? 0 : \App\Support\Fees::reservationFeeMinor();
            $req->total_paid_minor = $freeRetrySource ? 0 : (int) $req->total_paid_minor;
            $req->retry_source_request_id = $freeRetrySource?->id;
            $req->save();

            app(\App\Services\Admin\PlanScheduleService::class)->initializeUserSchedule($req);

            if ($freeRetrySource) {
                $this->ensureConversationExists($req);
            } else {
                NotifyEligibleTrainers::dispatch($req);
            }

            return $req;
        });
    }

    public function markAwaitingOffers(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_AWAITING_OFFERS;
        $req->save();
        NotifyEligibleTrainers::dispatch($req);
    }

    public function selectOffer(UserRequest $req, TrainerOffer $offer): void
    {
        DB::transaction(function () use ($req, $offer) {
            $req->status = UserRequest::STATUS_OFFER_SELECTED;
            $req->trainer_id = $offer->trainer_id;
            $req->save();
            TrainerOffer::where('user_request_id', $req->id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => TrainerOffer::STATUS_REJECTED]);
            $offer->status = TrainerOffer::STATUS_ACCEPTED;
            $offer->save();
        });
    }

    public function markInTraining(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_IN_TRAINING;
        $req->save();

        $progressCount = \App\Models\UserScheduleProgress::where('user_request_id', $req->id)->count();
        if ($progressCount === 0) {
            app(\App\Services\Admin\PlanScheduleService::class)->initializeUserSchedule($req);
        }

        $this->ensureConversationExists($req);
    }

    public function complete(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_COMPLETED;
        $req->save();
    }

    private function findEligibleFreeRetrySource(string $userId, string $planId, ?string $trainerId): ?UserRequest
    {
        if (!$trainerId) {
            return null;
        }

        return UserRequest::query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('trainer_id', $trainerId)
            ->where('status', UserRequest::STATUS_CANCELLED)
            ->whereDoesntHave('retryChild')
            ->latest('updated_at')
            ->first();
    }

    private function ensureConversationExists(UserRequest $req): void
    {
        if (!$req->trainer_id || !$req->user_id) {
            return;
        }

        $trainer = \App\Models\User::find($req->trainer_id);
        $user = \App\Models\User::find($req->user_id);
        if ($trainer && $user) {
            app(ConversationService::class)->findOrCreateConversation($trainer, $user);
        }
    }
}
