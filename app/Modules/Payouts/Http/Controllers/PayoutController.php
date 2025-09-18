<?php

declare(strict_types=1);

namespace App\Modules\Payouts\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PayoutController extends BaseController
{
    public function index(Request $request)
    {
        $q = Payout::query();
        if ($request->query('mine') === 'trainer') {
            $q->where('trainer_id', $request->user()->id);
        }
        return response()->json(['data' => $q->latest()->paginate(20)]);
    }
}

