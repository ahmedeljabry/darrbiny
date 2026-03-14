<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\WalletTransaction;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WithdrawalRequestsController extends BaseController
{
    public function __construct(private readonly WalletService $wallets) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $search = $request->query('q');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = WalletTransaction::query()
            ->with(['user', 'processedBy'])
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST);

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

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'phone_with_cc', 'user_type']);

        return view('admin.withdrawal-requests.index', compact('requests', 'users', 'status', 'userId', 'search', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $request = WalletTransaction::query()
            ->with(['user', 'processedBy'])
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->findOrFail($id);

        return view('admin.withdrawal-requests.show', ['withdrawalRequest' => $request]);
    }

    public function approve(Request $request, string $id)
    {
        $withdrawalRequest = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->findOrFail($id);

        if ($withdrawalRequest->status !== WalletTransaction::STATUS_PENDING) {
            return back()->withErrors(['error' => 'لا يمكن تنفيذ طلب غير معلق']);
        }

        $this->wallets->approveWithdrawal($withdrawalRequest, $request->user());

        return back()->with('status', 'تم تنفيذ طلب السحب وخصم الرصيد من المحفظة');
    }

    public function reject(Request $request, string $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $withdrawalRequest = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->findOrFail($id);

        if ($withdrawalRequest->status !== WalletTransaction::STATUS_PENDING) {
            return back()->withErrors(['error' => 'لا يمكن رفض طلب غير معلق']);
        }

        $this->wallets->rejectWithdrawal(
            $withdrawalRequest,
            $request->user(),
            (string) $data['rejection_reason']
        );

        return back()->with('status', 'تم رفض طلب السحب');
    }
}

