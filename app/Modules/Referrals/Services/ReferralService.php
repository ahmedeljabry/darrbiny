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
        // Give 1 point to the referral code owner
        $pointsOwner = 1;
        $owner->increment('points_balance', $pointsOwner);

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

