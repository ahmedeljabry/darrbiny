<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Models\RewardRedemption;
use Illuminate\Routing\Controller as BaseController;

class AdminRewardRedemptionController extends BaseController
{
    public function index()
    {
        $this->authorize('admin');
        return response()->json(['data' => RewardRedemption::latest()->paginate(50)]);
    }

    public function approve(string $id)
    {
        $this->authorize('admin');
        $r = RewardRedemption::findOrFail($id);
        $r->status = 'approved';
        $r->save();
        return response()->json(['data' => $r]);
    }

    public function reject(string $id)
    {
        $this->authorize('admin');
        $r = RewardRedemption::findOrFail($id);
        $r->status = 'rejected';
        $r->save();
        return response()->json(['data' => $r]);
    }
}

