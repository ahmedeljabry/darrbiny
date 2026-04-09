<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use App\Modules\Referrals\Services\ReferralService;
use App\Modules\Requests\Services\RequestService;
use App\Support\Fees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly RequestService $requests,
        private readonly ReferralService $referrals,
    ) {}

    /**
     * Pay with wallet balance
     * Deducts amount from user's wallet (points_balance) and creates payment record
     */
    public function payWithWallet(UserRequest $req, User $user, Request $request): Payment
    {
        $paymentType = (string) $request->input('type', Payment::TYPE_PLAN_FULL);
        abort_unless(
            in_array($paymentType, [Payment::TYPE_PLAN_FULL, Payment::TYPE_PLAN_PARTIAL], true),
            422,
            'Invalid type'
        );

        if ($paymentType === Payment::TYPE_PLAN_FULL) {
            abort_unless(in_array($request->status, [Payment::STATUS_PENDING, Payment::STATUS_SUCCEEDED, Payment::STATUS_FAILED], true), 422, 'Invalid status');
            $amountMinor = $this->resolveFullPaymentAmountMinor($req, $request);
        } else {
            $amountMinor = (int) $request->input('price', 0);
            abort_unless($amountMinor > 0, 422, 'Payment amount is required');
        }

        return DB::transaction(function () use ($req, $user, $amountMinor, $request, $paymentType) {
            $isFirstSuccessfulPlanPayment = false;

            if (
                $paymentType === Payment::TYPE_PLAN_FULL &&
                $request->status === Payment::STATUS_SUCCEEDED &&
                ! empty($user->referred_by)
            ) {
                User::query()->whereKey($user->id)->lockForUpdate()->first();
                $isFirstSuccessfulPlanPayment = ! Payment::query()
                    ->where('user_id', $user->id)
                    ->where('type', Payment::TYPE_PLAN_FULL)
                    ->where('status', Payment::STATUS_SUCCEEDED)
                    ->exists();
            }

            $appFeeMinor = 0;
            $trainerNetMinor = $amountMinor;

            if ($paymentType === Payment::TYPE_PLAN_FULL) {
                $appFeePercent = max(0, Fees::appFeePercent());
                $appFeeMinor = (int) round($amountMinor * ($appFeePercent / 100));
                $appFeeMinor = min($appFeeMinor, $amountMinor);
                $trainerNetMinor = $amountMinor - $appFeeMinor;
            }

            $payment = Payment::create([
                'user_id' => $user->id,
                'user_request_id' => $req->id,
                'amount_minor' => $amountMinor,
                'currency' => $req->currency,
                'type' => $paymentType,
                'payment_method' => $request->payment_method,
                'status' => $request->status,
                'app_fee_minor' => $appFeeMinor,
                'trainer_net_minor' => $trainerNetMinor,
            ]);
            if ($paymentType === Payment::TYPE_PLAN_FULL) {
                $req->app_fee_reserved_minor = $payment->app_fee_minor;
                $req->total_paid_minor = $amountMinor;
                $req->save();
                $this->requests->markInTraining($req);
            } elseif ($paymentType === Payment::TYPE_PLAN_PARTIAL) {
                $req->total_paid_minor = $amountMinor;
                $req->save();
                $this->requests->markAwaitingOffers($req);
            }

            if ($isFirstSuccessfulPlanPayment) {
                $this->referrals->awardPaidSubscriptionPoint($user);
            }

            return $payment;
        });
    }

    private function resolveFullPaymentAmountMinor(UserRequest $req, Request $request): int
    {
        $req->loadMissing('plan');

        $acceptedOffer = $req->offers()
            ->where('status', TrainerOffer::STATUS_ACCEPTED)
            ->latest('created_at')
            ->first();

        $amountMinor = (int) ($acceptedOffer?->price_minor ?? 0);

        if ($amountMinor <= 0) {
            $amountMinor = (int) ($req->plan?->price_min ?? 0) * 100;
        }

        if ($amountMinor <= 0) {
            $amountMinor = (int) $request->input('price', 0);
        }

        abort_unless($amountMinor > 0, 422, 'Unable to determine payment amount');

        return $amountMinor;
    }
}
