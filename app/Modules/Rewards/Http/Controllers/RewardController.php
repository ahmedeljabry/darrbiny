<?php

declare(strict_types=1);

namespace App\Modules\Rewards\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Modules\Rewards\Http\Requests\PrizeRequestRequest;
use App\Notifications\PrizeRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class RewardController extends BaseController
{
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
                    'image' => $prize->image ? Storage::disk(config('filesystems.default', 'public'))->url($prize->image) : null,
                ];
            });

        return response()->json(['data' => $prizes]);
    }

    public function requestPrize(PrizeRequestRequest $request)
    {
        $user = $request->user();
        $reward = Reward::findOrFail($request->input('reward_id'));
        $pointsSpent = $request->input('points_spent');

        // Validate reward is available
        abort_unless($reward->active, 422, 'الجائزة غير متاحة');
        
        // Validate user has enough points
        abort_unless($user->points_balance >= $pointsSpent, 422, 'ليس لديك نقاط كافية');

        // Validate points is at least the minimum required
        abort_unless($pointsSpent >= $reward->required_points, 422, 'عدد النقاط يجب أن يكون على الأقل ' . $reward->required_points);

        return DB::transaction(function () use ($user, $reward, $pointsSpent) {
            // Create redemption request (don't deduct points yet - wait for admin approval)
            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $pointsSpent,
                'status' => 'pending',
            ]);

            // Notify all admins
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

