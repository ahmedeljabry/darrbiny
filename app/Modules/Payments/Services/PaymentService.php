<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\TrainerOffer;
use App\Models\UserRequest;
use App\Modules\Requests\Services\RequestService;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly RequestService $requests,
    ) {}

    public function payReservation(UserRequest $req, string $userId): Payment
    {
        abort_unless($req->status === UserRequest::STATUS_PENDING_PAYMENT, 422, 'Invalid status');
        return DB::transaction(function () use ($req, $userId) {
            $payment = Payment::create([
                'user_id' => $userId,
                'user_request_id' => $req->id,
                'amount_minor' => (int) config('app.reservation_fee_minor', 1000),
                'currency' => $req->currency,
                'type' => Payment::TYPE_RESERVATION_FEE,
                'provider' => 'dummy',
                'provider_ref' => (string) \Str::uuid(),
                'status' => Payment::STATUS_SUCCEEDED,
                'app_fee_minor' => (int) config('app.reservation_fee_minor', 1000),
                'trainer_net_minor' => 0,
            ]);
            $req->status = UserRequest::STATUS_AWAITING_OFFERS;
            $req->app_fee_reserved_minor = $payment->app_fee_minor;
            $req->save();
            $this->requests->markAwaitingOffers($req);
            return $payment;
        });
    }

    public function payPlan(UserRequest $req, string $userId): Payment
    {
        abort_unless($req->status === UserRequest::STATUS_OFFER_SELECTED, 422, 'No offer selected');
        $offer = TrainerOffer::where('user_request_id', $req->id)->where('status', TrainerOffer::STATUS_ACCEPTED)->firstOrFail();

        return DB::transaction(function () use ($req, $offer, $userId) {
            $feePercent = (float) config('app.app_fee_percent', 10.0);
            $appFee = (int) round($offer->price_minor * ($feePercent/100));
            $trainerNet = $offer->price_minor - $appFee;
            $payment = Payment::create([
                'user_id' => $userId,
                'user_request_id' => $req->id,
                'amount_minor' => $offer->price_minor,
                'currency' => $req->currency,
                'type' => Payment::TYPE_PLAN_FULL,
                'provider' => 'dummy',
                'provider_ref' => (string) \Str::uuid(),
                'status' => Payment::STATUS_SUCCEEDED,
                'app_fee_minor' => $appFee,
                'trainer_net_minor' => $trainerNet,
            ]);
            $req->status = UserRequest::STATUS_IN_TRAINING;
            $req->total_paid_minor = $offer->price_minor + $req->app_fee_reserved_minor;
            $req->save();
            $this->requests->markInTraining($req);
            return $payment;
        });
    }
}

