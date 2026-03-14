<?php

declare(strict_types=1);

namespace App\Modules\Referrals\Services;

use App\Models\Payment;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralPointsAddedNotification;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function processSignupReferral(User $newUser, string $code): void
    {
        $owner = User::where('referral_code', $code)->first();
        if (!$owner || $owner->id === $newUser->id || !empty($newUser->referred_by)) {
            return;
        }

        DB::transaction(function () use ($owner, $newUser): void {
            Referral::firstOrCreate(
                ['owner_user_id' => $owner->id],
                ['code' => $owner->referral_code]
            );

            $newUser->referred_by = $owner->id;
            $newUser->save();
        });
    }

    public function awardPaidSubscriptionPoint(User $referredUser, int $points = 1): void
    {
        if ($points < 1 || empty($referredUser->referred_by)) {
            return;
        }

        $owner = User::query()->find($referredUser->referred_by);
        if (!$owner || $owner->id === $referredUser->id) {
            return;
        }

        $referral = Referral::query()->firstOrCreate(
            ['owner_user_id' => $owner->id],
            ['code' => $owner->referral_code]
        );

        $referral->increment('total_points_earned', $points);
        $owner->notify(new ReferralPointsAddedNotification($points, $referredUser->name));
    }

    public function syncOwnerPaidSubscriptionPoints(User $owner): Referral
    {
        $referral = Referral::query()->firstOrCreate(
            ['owner_user_id' => $owner->id],
            ['code' => $owner->referral_code]
        );

        $earnedPointsFromPayments = User::query()
            ->where('referred_by', $owner->id)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.user_id', 'users.id')
                    ->where('payments.type', Payment::TYPE_PLAN_FULL)
                    ->where('payments.status', Payment::STATUS_SUCCEEDED);
            })
            ->count();

        $currentPoints = (int) $referral->total_points_earned;
        if ($earnedPointsFromPayments > $currentPoints) {
            $referral->increment('total_points_earned', $earnedPointsFromPayments - $currentPoints);
            $referral->refresh();
        }

        return $referral;
    }
}
