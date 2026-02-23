<?php

declare(strict_types=1);

namespace App\Modules\Referrals\Services;

use App\Models\Referral;
use App\Models\User;
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
}
