<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\WalletTransactionsExport;
use App\Models\WalletTransaction;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

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

        $query = WalletTransaction::with(['user.country', 'processedBy']);

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

        if ($request->query('export') === 'excel') {
            $allTransactions = WalletTransaction::with(['user.country', 'processedBy'])
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('phone_with_cc', 'like', "%{$search}%");
                    });
                })
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->orderBy('created_at', 'desc')
                ->get();

            return Excel::download(
                new WalletTransactionsExport($allTransactions),
                'wallet-transactions-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'phone_with_cc']);

        return view('admin.wallet-transactions.index', compact('transactions', 'users', 'status', 'userId', 'search', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $transaction = WalletTransaction::with(['user.country', 'processedBy'])->findOrFail($id);
        return view('admin.wallet-transactions.show', compact('transaction'));
    }

    public function approve(Request $request, string $id)
    {
        $transaction = WalletTransaction::findOrFail($id);
        
        if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
            return back()->withErrors(['error' => 'لا يمكن الموافقة على معاملة غير معلقة']);
        }

        if ($transaction->type === WalletTransaction::TYPE_TOPUP_REQUEST) {
            $this->service->approveTopup($transaction, $request->user());
            return back()->with('status', 'تمت الموافقة على طلب الإضافة وإيداع المبلغ في المحفظة');
        }

        if ($transaction->type === WalletTransaction::TYPE_WITHDRAW_REQUEST) {
            $this->service->approveWithdrawal($transaction, $request->user());
            return back()->with('status', 'تم تنفيذ طلب السحب وخصم المبلغ من المحفظة');
        }

        return back()->withErrors(['error' => 'نوع المعاملة غير مدعوم للموافقة']);
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

        if ($transaction->type === WalletTransaction::TYPE_TOPUP_REQUEST) {
            $this->service->rejectTopup($transaction, $request->user(), $request->input('rejection_reason'));
            return back()->with('status', 'تم رفض طلب الإضافة');
        }

        if ($transaction->type === WalletTransaction::TYPE_WITHDRAW_REQUEST) {
            $this->service->rejectWithdrawal($transaction, $request->user(), $request->input('rejection_reason'));
            return back()->with('status', 'تم رفض طلب السحب');
        }

        return back()->withErrors(['error' => 'نوع المعاملة غير مدعوم للرفض']);
    }
}
