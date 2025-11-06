<?php

declare(strict_types=1);

namespace App\Modules\Referrals\Http\Controllers;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class ReferralController extends BaseController
{
    public function me(Request $request)
    {
        $user = $request->user();
        $r = Referral::firstOrCreate([
            'owner_user_id' => $user->id,
        ], [
            'code' => $user->referral_code,
        ]);

        // Get users who logged in with this referral code
        $referredUsers = User::where('referred_by', $user->id)
            ->select('id', 'name', 'phone_with_cc', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($referredUser) {
                return [
                    'id' => $referredUser->id,
                    'name' => $referredUser->name,
                    'phone_with_cc' => $referredUser->phone_with_cc,
                    'joined_at' => $referredUser->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['data' => [
            'code' => $r->code,
            'points_balance' => $user->points_balance,
            'total_points_earned' => $r->total_points_earned,
            'total_redemptions' => $r->total_redemptions,
            'referred_users' => $referredUsers,
        ]]);
    }
}

