<?php

declare(strict_types=1);

namespace App\Modules\Requests\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Payout;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Jobs\NotifyEligibleTrainers;
use App\Notifications\WalletBalanceAddedNotification;
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

    public function complete(UserRequest $req, ?User $completedBy = null): void
    {
        DB::transaction(function () use ($req, $completedBy): void {
            $req = UserRequest::query()
                ->with(['payments', 'trainer'])
                ->lockForUpdate()
                ->findOrFail($req->id);

            if ($req->status !== UserRequest::STATUS_COMPLETED) {
                $req->status = UserRequest::STATUS_COMPLETED;
                $req->save();
            }

            $payment = $req->latestSuccessfulFullPayment();
            if (!$payment || !$req->trainer_id) {
                return;
            }

            $this->syncTrainerPayout($req, $payment, $completedBy);
        });
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

    private function syncTrainerPayout(UserRequest $req, Payment $payment, ?User $completedBy = null): void
    {
        $walletAmount = (int) round($payment->trainer_net_minor / 100);
        if ($walletAmount <= 0) {
            return;
        }

        Payout::query()->firstOrCreate(
            ['user_request_id' => $req->id],
            [
                'trainer_id' => $req->trainer_id,
                'amount_minor' => $payment->trainer_net_minor,
                'currency' => $payment->currency ?: $req->currency,
                'status' => Payout::STATUS_PENDING_REVIEW,
            ]
        );

        $notes = $this->buildTrainerPayoutWalletNote($req);

        $existingWalletTransaction = WalletTransaction::query()
            ->where('user_id', $req->trainer_id)
            ->where('type', WalletTransaction::TYPE_ADJUSTMENT)
            ->where('notes', $notes)
            ->first();

        if ($existingWalletTransaction) {
            return;
        }

        $trainer = User::query()
            ->whereKey($req->trainer_id)
            ->lockForUpdate()
            ->first();

        if (!$trainer) {
            return;
        }

        $walletTransaction = WalletTransaction::create([
            'user_id' => $trainer->id,
            'amount' => $walletAmount,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => $notes,
            'processed_by' => $completedBy?->id,
            'processed_at' => now(),
        ]);

        $trainer->increment('points_balance', $walletAmount);
        $trainer->notify(new WalletBalanceAddedNotification(
            $walletAmount,
            'course_payout',
            $walletTransaction->id
        ));
    }

    private function buildTrainerPayoutWalletNote(UserRequest $req): string
    {
        return 'إضافة مستحقات كورس رقم ' . $req->id;
    }
}
