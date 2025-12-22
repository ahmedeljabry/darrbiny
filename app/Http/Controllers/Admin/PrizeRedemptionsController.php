<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PrizeRedemptionsExport;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PrizeRedemptionsController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');
        
        $query = RewardRedemption::with(['user', 'reward']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_with_cc', 'like', "%{$search}%");
            });
        }
        
        $redemptions = $query->latest()->paginate(20)->withQueryString();

        if ($request->query('export') === 'excel') {
            $allRedemptions = RewardRedemption::with(['user', 'reward'])
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('phone_with_cc', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->get();

            return Excel::download(
                new PrizeRedemptionsExport($allRedemptions),
                'prize-redemptions-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.prize-redemptions.index', compact('redemptions', 'status', 'search'));
    }

    public function show(string $id)
    {
        $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
        return view('admin.prize-redemptions.show', compact('redemption'));
    }

    public function approve(string $id)
    {
        return DB::transaction(function () use ($id) {
            $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
            
            abort_unless($redemption->status === 'pending', 422, 'لا يمكن الموافقة على طلب غير معلق');
            
            $redemption->user->decrement('points_balance', $redemption->points_spent);
            $redemption->status = 'approved';
            $redemption->save();
            
            return back()->with('status', 'تم الموافقة على الطلب وخصم النقاط');
        });
    }

    public function reject(Request $request, string $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);
        
        return DB::transaction(function () use ($id, $data) {
            $redemption = RewardRedemption::with(['user', 'reward'])->findOrFail($id);
            
            abort_unless($redemption->status === 'pending', 422, 'لا يمكن رفض طلب غير معلق');
            
            $redemption->status = 'rejected';
            $redemption->rejection_reason = $data['rejection_reason'];
            $redemption->save();
            
            return back()->with('status', 'تم رفض الطلب');
        });
    }
}

