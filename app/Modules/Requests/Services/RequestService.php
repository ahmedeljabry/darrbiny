<?php

declare(strict_types=1);

namespace App\Modules\Requests\Services;

use App\Models\Plan;
use App\Models\TrainerOffer;
use App\Models\UserRequest;
use Illuminate\Support\Facades\DB;

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
            $req->app_fee_reserved_minor = (int) config('app.reservation_fee_minor', 1000);
            $req->save();
            // TODO: dispatch broadcast job to eligible trainers
            return $req;
        });
    }

    public function markAwaitingOffers(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_AWAITING_OFFERS;
        $req->save();
        // TODO: notify trainers in same city/country
    }

    public function selectOffer(UserRequest $req, TrainerOffer $offer): void
    {
        DB::transaction(function () use ($req, $offer) {
            $req->status = UserRequest::STATUS_OFFER_SELECTED;
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
        // TODO: create secure conversation stub
    }

    public function complete(UserRequest $req): void
    {
        $req->status = UserRequest::STATUS_COMPLETED;
        $req->save();
    }
}

