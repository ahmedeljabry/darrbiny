<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use App\Modules\Referrals\Services\ReferralService;
use App\Modules\Requests\Services\RequestService;
use App\Modules\Wallet\Services\WalletService;
use App\Support\Fees;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly RequestService $requests,
        private readonly ReferralService $referrals,
        private readonly WalletService $wallets,
        private readonly PaymentGatewayFactory $gateways,
    ) {}

    /**
     * Pay with wallet balance
     * Deducts amount from user's wallet (points_balance) and creates payment record
     */
    public function payWithWallet(UserRequest $req, User $user, Request $request): Payment
    {
        return $this->createPlanPayment($req, $user, $request);
    }

    public function createPlanPayment(UserRequest $req, User $user, Request $request): Payment
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

        $payment = DB::transaction(function () use ($req, $user, $amountMinor, $request, $paymentType) {
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

            if ($payment->status === Payment::STATUS_SUCCEEDED) {
                $this->applySuccessfulPaymentEffects(
                    $payment,
                    $user,
                    $request->payment_method === Payment::METHOD_WALLET,
                    $isFirstSuccessfulPlanPayment
                );
            }

            return $payment;
        });

        if ($this->requiresGatewaySession((string) $payment->payment_method) && $payment->status === Payment::STATUS_PENDING) {
            $payment = $this->initiateGatewaySession($payment);
        }

        return $payment;
    }

    public function markGatewayPaymentSucceeded(Payment $payment, ?array $gatewayPayload = null, ?string $gatewayStatus = null): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayPayload, $gatewayStatus): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($gatewayPayload !== null) {
                $payment->gateway_payload = $this->mergeGatewayPayload($payment->gateway_payload, $gatewayPayload);
            }

            if ($gatewayStatus !== null) {
                $payment->gateway_status = $gatewayStatus;
            }

            if ($payment->status === Payment::STATUS_SUCCEEDED) {
                $payment->save();

                return $payment;
            }

            $user = User::query()->whereKey($payment->user_id)->lockForUpdate()->firstOrFail();
            $isFirstSuccessfulPlanPayment = $this->isFirstSuccessfulPlanPayment($payment, $user);

            $payment->status = Payment::STATUS_SUCCEEDED;
            $payment->save();

            $this->applySuccessfulPaymentEffects($payment, $user, false, $isFirstSuccessfulPlanPayment);

            return $payment->refresh();
        });
    }

    public function markGatewayPaymentFailed(Payment $payment, ?array $gatewayPayload = null, ?string $gatewayStatus = null): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayPayload, $gatewayStatus): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === Payment::STATUS_SUCCEEDED) {
                return $payment;
            }

            if ($gatewayPayload !== null) {
                $payment->gateway_payload = $this->mergeGatewayPayload($payment->gateway_payload, $gatewayPayload);
            }

            $payment->gateway_status = $gatewayStatus ?? $payment->gateway_status;
            $payment->status = Payment::STATUS_FAILED;
            $payment->save();

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

        abort_unless(
            filled($req->trainer_id) || $acceptedOffer,
            422,
            'Please accept a trainer offer before full payment'
        );

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

    private function initiateGatewaySession(Payment $payment): Payment
    {
        try {
            $session = $this->gateways
                ->forMethod((string) $payment->payment_method)
                ->initiate($payment);
        } catch (\Throwable $exception) {
            $gatewayException = $exception instanceof PaymentGatewayException
                ? $exception
                : new PaymentGatewayException(
                    'Payment gateway session could not be created.',
                    ['error' => $exception->getMessage()],
                    previous: $exception
                );

            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'gateway_status' => 'initiation_failed',
                'gateway_payload' => [
                    'error' => $gatewayException->getMessage(),
                    'context' => $gatewayException->context,
                ],
            ])->save();

            throw $gatewayException;
        }

        $payment->forceFill([
            'gateway_reference' => $session['gateway_reference'] ?? $session['reference'] ?? $payment->id,
            'gateway_checkout_url' => $session['checkout_url'] ?? null,
            'gateway_status' => $session['gateway_status'] ?? $session['status'] ?? null,
            'gateway_payload' => $session['payload'] ?? $session,
        ])->save();

        return $payment->refresh();
    }

    private function requiresGatewaySession(string $paymentMethod): bool
    {
        return in_array($paymentMethod, [Payment::METHOD_TAP, Payment::METHOD_TABBY, Payment::METHOD_TAMARA], true);
    }

    private function applySuccessfulPaymentEffects(
        Payment $payment,
        User $user,
        bool $deductWallet,
        bool $isFirstSuccessfulPlanPayment,
    ): void {
        $req = UserRequest::query()
            ->whereKey($payment->user_request_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($deductWallet) {
            $this->wallets->deduct(
                $user,
                WalletAmount::minorToMajor((int) $payment->amount_minor),
                null,
                $payment->id
            );
        }

        if ($payment->type === Payment::TYPE_PLAN_FULL) {
            $req->app_fee_reserved_minor = $payment->app_fee_minor;
            $req->total_paid_minor = $payment->amount_minor;
            $req->save();
            $this->requests->markInTraining($req);
        } elseif ($payment->type === Payment::TYPE_PLAN_PARTIAL) {
            $req->total_paid_minor = $payment->amount_minor;
            $req->save();
            $this->requests->markAwaitingOffers($req);
        }

        if ($isFirstSuccessfulPlanPayment) {
            $this->referrals->awardPaidSubscriptionPoint($user);
        }
    }

    private function isFirstSuccessfulPlanPayment(Payment $payment, User $user): bool
    {
        if ($payment->type !== Payment::TYPE_PLAN_FULL || empty($user->referred_by)) {
            return false;
        }

        return ! Payment::query()
            ->where('user_id', $user->id)
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereKeyNot($payment->id)
            ->exists();
    }

    private function mergeGatewayPayload(mixed $existing, array $payload): array
    {
        $existing = is_array($existing) ? $existing : [];

        return array_merge($existing, ['latest_event' => $payload]);
    }
}
