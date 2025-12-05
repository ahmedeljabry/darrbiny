<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Modules\Wallet\Http\Requests\TopupRequestRequest;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletController extends BaseController
{
    public function __construct(private readonly WalletService $service) {}

    /**
     * Request wallet top-up
     */
    public function requestTopup(TopupRequestRequest $request)
    {
        $user = $request->user();

        $transaction = $this->service->requestTopup(
            $user,
            $request->input('amount'),
            $request->input('notes')
        );

        return response()->json([
            'message' => 'تم إرسال طلب إضافة المبلغ بنجاح',
            'data' => new \App\Modules\Wallet\Http\Resources\WalletTransactionResource($transaction),
        ], 201);
    }

    /**
     * Get wallet balance and summary
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get transaction statistics
        $totalTransactions = \App\Models\WalletTransaction::where('user_id', $user->id)->count();
        $pendingTransactions = \App\Models\WalletTransaction::where('user_id', $user->id)
            ->where('status', \App\Models\WalletTransaction::STATUS_PENDING)
            ->count();
        $approvedTransactions = \App\Models\WalletTransaction::where('user_id', $user->id)
            ->where('status', \App\Models\WalletTransaction::STATUS_APPROVED)
            ->count();

        return response()->json([
            'data' => [
                'balance' => $user->points_balance,
                'currency' => $user->currency ?? 'USD',
                'statistics' => [
                    'total_transactions' => $totalTransactions,
                    'pending_transactions' => $pendingTransactions,
                    'approved_transactions' => $approvedTransactions,
                ],
            ],
        ]);
    }

    /**
     * Get wallet transactions with pagination
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 20);
        $status = $request->query('status'); // pending, approved, rejected
        $type = $request->query('type'); // topup_request, refund, payment, adjustment

        $query = \App\Models\WalletTransaction::where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return \App\Modules\Wallet\Http\Resources\WalletTransactionResource::collection($transactions)->response();
    }

    /**
     * Get latest transactions for user profile (legacy - kept for backward compatibility)
     */
    public function latestTransactions(Request $request)
    {
        $user = $request->user();
        $transactions = $this->service->getLatestTransactions($user, 3);

        return \App\Modules\Wallet\Http\Resources\WalletTransactionResource::collection($transactions)->response();
    }
}

