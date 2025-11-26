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
            $req = new UserRequest($data);
            $req->user_id = $userId;
            $req->status = UserRequest::STATUS_PENDING_PAYMENT;
            $req->currency = auth()->user()?->currency ?? 'USD';
            $req->app_fee_reserved_minor = \App\Support\Fees::reservationFeeMinor();
            $req->save();

            // Initialize schedule progress when user subscribes
            app(\App\Services\Admin\PlanScheduleService::class)->initializeUserSchedule($req);

            // Dispatch notifications to eligible trainers
            NotifyEligibleTrainers::dispatch($req);
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

        // Ensure schedule progress is initialized if not already
        $progressCount = \App\Models\UserScheduleProgress::where('user_request_id', $req->id)->count();
        if ($progressCount === 0) {
            app(\App\Services\Admin\PlanScheduleService::class)->initializeUserSchedule($req);
        }

        // Ensure conversation exists between المتدرب والمدرب
        if ($req->trainer_id && $req->user_id) {
            $trainer = \App\Models\User::find($req->trainer_id);
            $user = \App\Models\User::find($req->user_id);
            if ($trainer && $user) {
                app(ConversationService::class)->findOrCreateConversation($trainer, $user);
            }
        }
    }

    public function complete(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_COMPLETED;
        $req->save();
    }
}
