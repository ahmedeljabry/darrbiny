<?php

declare(strict_types=1);

namespace App\Modules\Referrals\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    public function processSignupReferral(User $newUser, string $code): void
    {
        $owner = User::where('referral_code', $code)->first();
        if (!$owner || $owner->id === $newUser->id) {
            return;
        }
        $pointsOwner = (int) config('app.referral_points_owner', 50);
        $pointsNew = (int) config('app.referral_points_new', 20);
        $owner->increment('points_balance', $pointsOwner);
        $newUser->increment('points_balance', $pointsNew);

        Referral::updateOrCreate(
            ['owner_user_id' => $owner->id],
            [
                'code' => $owner->referral_code,
                'total_points_earned' => \DB::raw('total_points_earned + '.$pointsOwner),
            ]
        );
        $newUser->referred_by = $owner->id;
        $newUser->save();
    }
}

