<?php

declare(strict_types=1);

namespace App\Modules\Rewards\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RewardController extends BaseController
{
    public function index()
    {
        return response()->json(['data' => Reward::where('active', true)->orderBy('required_points')->get()]);
    }

    public function redeem(Request $request)
    {
        $data = $request->validate([
            'reward_id' => ['required','uuid'],
        ]);
        $reward = Reward::findOrFail($data['reward_id']);
        abort_unless($reward->active && $reward->stock > 0, 422, 'Reward unavailable');
        abort_unless($request->user()->points_balance >= $reward->required_points, 422, 'Not enough points');
        $redemption = RewardRedemption::create([
            'user_id' => $request->user()->id,
            'reward_id' => $reward->id,
            'points_spent' => $reward->required_points,
            'status' => 'pending',
        ]);
        $request->user()->decrement('points_balance', $reward->required_points);
        $reward->decrement('stock');
        return response()->json(['data' => $redemption], 201);
    }
}

