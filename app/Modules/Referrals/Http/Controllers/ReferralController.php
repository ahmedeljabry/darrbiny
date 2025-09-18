<?php

declare(strict_types=1);

namespace App\Modules\Referrals\Http\Controllers;

use App\Models\Referral;
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
        return response()->json(['data' => [
            'code' => $r->code,
            'total_points_earned' => $r->total_points_earned,
            'total_redemptions' => $r->total_redemptions,
        ]]);
    }
}

