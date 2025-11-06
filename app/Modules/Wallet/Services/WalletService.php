<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\WalletTopupRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class WalletService
{
    /**
     * Request wallet top-up
     */
    public function requestTopup(User $user, int $amount, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $notes) {
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => WalletTransaction::TYPE_TOPUP_REQUEST,
                'status' => WalletTransaction::STATUS_PENDING,
                'notes' => $notes,
            ]);

            // Notify all admins
            $admins = User::role('admin')->get();
            Notification::send($admins, new WalletTopupRequestNotification($transaction));

            return $transaction;
        });
    }

    /**
     * Approve wallet top-up request
     */
    public function approveTopup(WalletTransaction $transaction, User $admin): void
    {
        DB::transaction(function () use ($transaction, $admin) {
            if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
                throw new \Exception('Transaction is not pending');
            }

            $transaction->status = WalletTransaction::STATUS_APPROVED;
            $transaction->processed_by = $admin->id;
            $transaction->processed_at = now();
            $transaction->save();

            // Add amount to user's wallet
            $user = $transaction->user;
            $user->increment('points_balance', $transaction->amount);
        });
    }

    /**
     * Reject wallet top-up request
     */
    public function rejectTopup(WalletTransaction $transaction, User $admin, string $reason): void
    {
        if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
            throw new \Exception('Transaction is not pending');
        }

        $transaction->status = WalletTransaction::STATUS_REJECTED;
        $transaction->processed_by = $admin->id;
        $transaction->processed_at = now();
        $transaction->rejection_reason = $reason;
        $transaction->save();
    }

    /**
     * Get latest transactions for user
     */
    public function getLatestTransactions(User $user, int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        return WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

