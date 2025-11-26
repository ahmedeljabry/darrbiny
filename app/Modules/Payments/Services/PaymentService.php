<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\TrainerOffer;
use App\Models\UserRequest;
use App\Modules\Requests\Services\RequestService;
use App\Support\Fees;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly RequestService $requests,
    ) {}

    /**
     * Store reservation fee payment transaction from mobile app
     * Mobile app handles payment gateway, we just store the transaction
     */
    public function storeReservationPayment(
        UserRequest $req,
        string $userId,
        string $provider,
        string $providerRef,
        string $status,
        array $transactionData = []
    ): Payment {
        abort_unless($req->status === UserRequest::STATUS_PENDING_PAYMENT, 422, 'Invalid status');
        
        return DB::transaction(function () use ($req, $userId, $provider, $providerRef, $status, $transactionData) {
            $serviceFeeMinor = Fees::reservationFeeMinor();
            
            $payment = Payment::create([
                'user_id' => $userId,
                'user_request_id' => $req->id,
                'amount_minor' => $serviceFeeMinor,
                'currency' => $req->currency,
                'type' => Payment::TYPE_RESERVATION_FEE,
                'provider' => $provider,
                'provider_ref' => $providerRef,
                'status' => $status,
                'app_fee_minor' => $serviceFeeMinor,
                'trainer_net_minor' => 0,
            ]);

            // Update booking status only if payment succeeded
            if ($status === Payment::STATUS_SUCCEEDED) {
                $req->status = UserRequest::STATUS_AWAITING_OFFERS;
                $req->app_fee_reserved_minor = $payment->app_fee_minor;
                $req->save();
                $this->requests->markAwaitingOffers($req);
            }

            return $payment;
        });
    }

    /**
     * Store plan payment transaction from mobile app
     * Mobile app handles payment gateway, we just store the transaction
     */
    public function storePlanPayment(
        UserRequest $req,
        string $userId,
        string $provider,
        string $providerRef,
        string $status,
        array $transactionData = []
    ): Payment {
        abort_unless($req->status === UserRequest::STATUS_OFFER_SELECTED, 422, 'No offer selected');
        $offer = TrainerOffer::where('user_request_id', $req->id)
            ->where('status', TrainerOffer::STATUS_ACCEPTED)
            ->firstOrFail();

        return DB::transaction(function () use ($req, $offer, $userId, $provider, $providerRef, $status, $transactionData) {
            $feePercent = Fees::appFeePercent();
            $appFee = (int) round($offer->price_minor * ($feePercent / 100));
            $trainerNet = $offer->price_minor - $appFee;
            
            $payment = Payment::create([
                'user_id' => $userId,
                'user_request_id' => $req->id,
                'amount_minor' => $offer->price_minor,
                'currency' => $req->currency,
                'type' => Payment::TYPE_PLAN_FULL,
                'provider' => $provider,
                'provider_ref' => $providerRef,
                'status' => $status,
                'app_fee_minor' => $appFee,
                'trainer_net_minor' => $trainerNet,
            ]);

            // Update booking status only if payment succeeded
            if ($status === Payment::STATUS_SUCCEEDED) {
                $req->status = UserRequest::STATUS_IN_TRAINING;
                $req->total_paid_minor = $offer->price_minor + $req->app_fee_reserved_minor;
                $req->save();
                $this->requests->markInTraining($req);
            }

            return $payment;
        });
    }

    /**
     * Handle webhook from payment provider to update payment status
     */
    public function handleWebhook(string $provider, array $data): void
    {
        // Extract provider reference from webhook data
        $providerRef = $data['id'] ?? $data['transaction_id'] ?? $data['reference'] ?? null;
        abort_unless($providerRef, 400, 'Missing provider reference');

        $payment = Payment::where('provider', $provider)
            ->where('provider_ref', $providerRef)
            ->firstOrFail();

        // Update payment status based on webhook
        $newStatus = $this->mapWebhookStatus($provider, $data);
        if ($newStatus && $newStatus !== $payment->status) {
            $payment->status = $newStatus;
            $payment->save();

            // Update booking status if payment succeeded
            if ($newStatus === Payment::STATUS_SUCCEEDED) {
                $req = $payment->userRequest;
                if ($req && $req->status === UserRequest::STATUS_PENDING_PAYMENT && $payment->type === Payment::TYPE_RESERVATION_FEE) {
                    $req->status = UserRequest::STATUS_AWAITING_OFFERS;
                    $req->app_fee_reserved_minor = $payment->app_fee_minor;
                    $req->save();
                    $this->requests->markAwaitingOffers($req);
                }
            }
        }
    }

    /**
     * Map webhook status from provider to our status
     */
    private function mapWebhookStatus(string $provider, array $data): ?string
    {
        $status = $data['status'] ?? $data['state'] ?? null;
        
        if (!$status) {
            return null;
        }

        $statusLower = strtolower($status);
        
        if (in_array($statusLower, ['succeeded', 'completed', 'paid', 'captured', 'success'])) {
            return Payment::STATUS_SUCCEEDED;
        }
        
        if (in_array($statusLower, ['failed', 'declined', 'cancelled', 'error'])) {
            return Payment::STATUS_FAILED;
        }
        
        if (in_array($statusLower, ['pending', 'processing', 'authorized'])) {
            return Payment::STATUS_PENDING;
        }

        return null;
    }

    /**
     * Pay with wallet balance
     * Deducts amount from user's wallet (points_balance) and creates payment record
     */
    public function payWithWallet(UserRequest $req, \App\Models\User $user, string $paymentType): Payment
    {
        // Calculate amount based on payment type
        $offer = null;
        if ($paymentType === Payment::TYPE_RESERVATION_FEE) {
            abort_unless($req->status === UserRequest::STATUS_PENDING_PAYMENT, 422, 'Invalid status');
            $amountMinor = Fees::reservationFeeMinor();
        } else {
            abort_unless($req->status === UserRequest::STATUS_OFFER_SELECTED, 422, 'No offer selected');
            $offer = TrainerOffer::where('user_request_id', $req->id)
                ->where('status', TrainerOffer::STATUS_ACCEPTED)
                ->firstOrFail();
            $amountMinor = $offer->price_minor;
        }

        $amount = $amountMinor / 100;
        
        // Check wallet balance (points_balance stores amount in major units)
        abort_unless($user->points_balance >= $amount, 422, 'Insufficient wallet balance');

        return DB::transaction(function () use ($req, $user, $paymentType, $amountMinor, $amount) {
            // Deduct from wallet
            $user->decrement('points_balance', $amount);
            
            // Calculate fees
            $feePercent = Fees::appFeePercent();
            $appFeeMinor = $paymentType === Payment::TYPE_RESERVATION_FEE 
                ? $amountMinor 
                : (int) round($amountMinor * ($feePercent / 100));
            
            $trainerNetMinor = $paymentType === Payment::TYPE_RESERVATION_FEE 
                ? 0 
                : ($amountMinor - $appFeeMinor);

            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'user_request_id' => $req->id,
                'amount_minor' => $amountMinor,
                'currency' => $req->currency,
                'type' => $paymentType,
                'provider' => 'wallet',
                'provider_ref' => 'wallet_' . \Str::uuid(),
                'status' => Payment::STATUS_SUCCEEDED,
                'app_fee_minor' => $appFeeMinor,
                'trainer_net_minor' => $trainerNetMinor,
            ]);

            // Update booking status
            if ($paymentType === Payment::TYPE_RESERVATION_FEE) {
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

    // Keep old methods for backward compatibility (deprecated)
    public function payReservation(UserRequest $req, string $userId): Payment
    {
        return $this->storeReservationPayment($req, $userId, 'mobile_app', (string) \Str::uuid(), Payment::STATUS_SUCCEEDED);
    }

    public function payPlan(UserRequest $req, string $userId): Payment
    {
        $offer = TrainerOffer::where('user_request_id', $req->id)
            ->where('status', TrainerOffer::STATUS_ACCEPTED)
            ->firstOrFail();
        
        return $this->storePlanPayment($req, $userId, 'mobile_app', (string) \Str::uuid(), Payment::STATUS_SUCCEEDED);
    }
}
