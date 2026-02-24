<?php

declare(strict_types=1);

namespace App\Modules\Rewards\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Referral;
use App\Models\User;
use App\Modules\Referrals\Services\ReferralService;
use App\Modules\Rewards\Http\Requests\PrizeRequestRequest;
use App\Notifications\PrizeRequestNotification;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Support\StorageUrl;

class RewardController extends BaseController
{
    public function __construct(
        private readonly ReferralService $referrals,
    ) {}

    public function index()
    {
        $prizes = Reward::where('active', true)
            ->orderBy('order')
            ->orderBy('required_points')
            ->get()
            ->map(function ($prize) {
                return [
                    'id' => $prize->id,
                    'title' => $prize->title,
                    'required_points' => $prize->required_points,
                    'order' => $prize->order,
                    'image' => StorageUrl::make($prize->image),
                ];
            });

        return response()->json(['data' => $prizes]);
    }

    public function requestPrize(PrizeRequestRequest $request)
    {
        $user = $request->user();
        $reward = Reward::findOrFail($request->input('reward_id'));
        $pointsSpent = (int) $reward->required_points;
        abort_unless($reward->active, 422, 'الجائزة غير متاحة');

        return DB::transaction(function () use ($user, $reward, $pointsSpent) {
            $referral = $this->referrals->syncOwnerPaidSubscriptionPoints($user);
            $referral = Referral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingPoints = (int) RewardRedemption::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->sum('points_spent');

            $availableReferralPoints = max(
                0,
                (int) $referral->total_points_earned - (int) $referral->total_redemptions - $pendingPoints
            );

            abort_unless($availableReferralPoints >= $pointsSpent, 422, 'ليس لديك نقاط كافية');
            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $pointsSpent,
                'status' => 'pending',
            ]);

            $admins = User::role('admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new PrizeRequestNotification($redemption->load('user', 'reward')));
            }

            return response()->json([
                'message' => 'تم إرسال طلب الجائزة بنجاح',
                'data' => [
                    'id' => $redemption->id,
                    'reward_id' => $redemption->reward_id,
                    'points_spent' => $redemption->points_spent,
                    'status' => $redemption->status,
                    'created_at' => $redemption->created_at?->toIso8601String(),
                ],
            ], 201);
        });
    }
}
