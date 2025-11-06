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
     * Get latest transactions for user profile
     */
    public function latestTransactions(Request $request)
    {
        $user = $request->user();
        $transactions = $this->service->getLatestTransactions($user, 3);

        return \App\Modules\Wallet\Http\Resources\WalletTransactionResource::collection($transactions)->response();
    }
}

