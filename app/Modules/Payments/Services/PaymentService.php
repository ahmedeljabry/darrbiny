<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Support\{Fees};
use App\Modules\Requests\Services\RequestService;
use App\Models\{TrainerOffer,UserRequest,Payment,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly RequestService $requests,) {}

    /**
     * Pay with wallet balance
     * Deducts amount from user's wallet (points_balance) and creates payment record
     */
    public function payWithWallet(UserRequest $req, User $user , Request $request): Payment
    {
        if ($request->type === Payment::TYPE_PLAN_FULL) {
            abort_unless(in_array($request->status, [Payment::STATUS_PENDING,Payment::STATUS_SUCCEEDED,Payment::STATUS_FAILED]), 422, 'Invalid status');
            if (!$req->relationLoaded('plan')) $req->load('plan');
            $amountMinor = $request->price;
        } else {
            abort_unless($req->status === UserRequest::STATUS_OFFER_SELECTED, 422, 'No offer selected');
            $amountMinor = $request->price;
        }

        return DB::transaction(function () use ($req, $user, $amountMinor , $request) {
            $feePercent = Fees::appFeePercent();
            $appFeeMinor = round($amountMinor * ($feePercent / 100));
            $trainerNetMinor = ($amountMinor - $appFeeMinor);
            $payment = Payment::create([
                'user_id' => $user->id,
                'user_request_id' => $req->id,
                'amount_minor' => $amountMinor,
                'currency' => $req->currency,
                'type' => $request->type,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'app_fee_minor' => $appFeeMinor,
                'trainer_net_minor' => $trainerNetMinor,
            ]);
            if ($request->type === Payment::TYPE_PLAN_FULL) {
                $req->status = UserRequest::STATUS_AWAITING_OFFERS;
                $req->app_fee_reserved_minor = $payment->app_fee_minor;
                $req->save();
                $this->requests->markAwaitingOffers($req);
            } else {
                $req->status = UserRequest::STATUS_IN_TRAINING;
                $req->total_paid_minor = $amountMinor + $req->app_fee_reserved_minor;
                $req->save();
                $this->requests->markInTraining($req);
            }

            return $payment;
        });
    }
}
