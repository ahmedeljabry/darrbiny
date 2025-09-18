<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class AdminPayoutController extends BaseController
{
    public function index()
    {
        $this->authorize('admin');
        return response()->json(['data' => Payout::latest()->paginate(50)]);
    }

    public function approve(string $id)
    {
        $this->authorize('admin');
        $payout = Payout::findOrFail($id);
        $payout->status = Payout::STATUS_APPROVED;
        $payout->save();
        return response()->json(['data' => $payout]);
    }

    public function markSent(Request $request, string $id)
    {
        $this->authorize('admin');
        $data = $request->validate(['bank_ref' => ['required','string','max:120']]);
        $payout = Payout::findOrFail($id);
        $payout->status = Payout::STATUS_SENT;
        $payout->bank_ref = $data['bank_ref'];
        $payout->processed_at = now();
        $payout->save();
        return response()->json(['data' => $payout]);
    }
}

