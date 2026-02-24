<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Referral;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class AdminRewardRedemptionController extends BaseController
{
    public function index()
    {
        $this->authorize('admin');
        return response()->json(['data' => RewardRedemption::with(['user', 'reward'])->latest()->paginate(50)]);
    }

    public function approve(string $id)
    {
        $this->authorize('admin');
        
        return DB::transaction(function () use ($id) {
            $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
            
            abort_unless($redemption->status === 'pending', 422, 'لا يمكن الموافقة على طلب غير معلق');

            $referral = Referral::query()->firstOrCreate(
                ['owner_user_id' => $redemption->user_id],
                ['code' => (string) $redemption->user->referral_code]
            );

            $referral = Referral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->firstOrFail();

            $availableReferralPoints = max(
                0,
                (int) $referral->total_points_earned - (int) $referral->total_redemptions
            );
            abort_unless($availableReferralPoints >= (int) $redemption->points_spent, 422, 'رصيد نقاط الإحالات غير كافٍ');

            // Deduct points from referral balance
            $referral->increment('total_redemptions', (int) $redemption->points_spent);
            
            // Update redemption status
            $redemption->status = 'approved';
            $redemption->save();
            
            return response()->json([
                'message' => 'تم الموافقة على الطلب وخصم النقاط',
                'data' => $redemption->load('user', 'reward'),
            ]);
        });
    }

    public function reject(Request $request, string $id)
    {
        $this->authorize('admin');
        
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);
        
        return DB::transaction(function () use ($id, $data) {
            $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
            
            abort_unless($redemption->status === 'pending', 422, 'لا يمكن رفض طلب غير معلق');
            
            // Update redemption status with rejection reason
            $redemption->status = 'rejected';
            $redemption->rejection_reason = $data['rejection_reason'];
            $redemption->save();
            
            return response()->json([
                'message' => 'تم رفض الطلب',
                'data' => $redemption->load('user', 'reward'),
            ]);
        });
    }
}
