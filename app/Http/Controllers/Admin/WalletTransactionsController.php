<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\WalletTransaction;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletTransactionsController extends BaseController
{
    public function __construct(private readonly WalletService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $search = $request->query('q');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = WalletTransaction::with(['user', 'processedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_with_cc', 'like', "%{$search}%");
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'phone_with_cc']);

        return view('admin.wallet-transactions.index', compact('transactions', 'users', 'status', 'userId', 'search', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $transaction = WalletTransaction::with(['user', 'processedBy'])->findOrFail($id);
        return view('admin.wallet-transactions.show', compact('transaction'));
    }

    public function approve(Request $request, string $id)
    {
        $transaction = WalletTransaction::findOrFail($id);
        
        if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
            return back()->withErrors(['error' => 'لا يمكن الموافقة على معاملة غير معلقة']);
        }

        $this->service->approveTopup($transaction, $request->user());

        return back()->with('status', 'تم الموافقة على الطلب وإضافة المبلغ إلى المحفظة');
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $transaction = WalletTransaction::findOrFail($id);
        
        if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
            return back()->withErrors(['error' => 'لا يمكن رفض معاملة غير معلقة']);
        }

        $this->service->rejectTopup($transaction, $request->user(), $request->input('rejection_reason'));

        return back()->with('status', 'تم رفض الطلب');
    }
}

