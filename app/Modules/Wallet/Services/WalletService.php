<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\WalletBalanceAddedNotification;
use App\Notifications\WalletTopupRequestNotification;
use App\Notifications\WalletWithdrawalProcessedNotification;
use App\Notifications\WalletWithdrawRequestNotification;
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
            $admins = User::role('ADMIN')->get();
            Notification::send($admins, new WalletTopupRequestNotification($transaction));

            return $transaction;
        });
    }

    /**
     * Request wallet withdrawal
     */
    public function requestWithdrawal(User $user, int $amount, ?string $notes = null): WalletTransaction
    {
        abort_unless($amount > 0, 422, 'المبلغ يجب أن يكون أكبر من صفر');

        return DB::transaction(function () use ($user, $amount, $notes) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $hasBankDetails = filled($user->bank_account)
                && filled($user->iban)
                && filled($user->bank_name)
                && filled($user->bank_country_id);
            abort_unless($hasBankDetails, 422, 'يجب استكمال بيانات الحساب البنكي قبل طلب السحب');

            $pendingWithdrawals = (int) WalletTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
                ->where('status', WalletTransaction::STATUS_PENDING)
                ->sum('amount');

            $availableToWithdraw = max(0, (int) $user->points_balance - $pendingWithdrawals);
            abort_unless($availableToWithdraw >= $amount, 422, 'رصيد المحفظة غير كافٍ لطلب السحب');

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
                'status' => WalletTransaction::STATUS_PENDING,
                'notes' => $notes,
            ]);

            // Notify all admins
            $admins = User::role('ADMIN')->get();
            Notification::send($admins, new WalletWithdrawRequestNotification($transaction));

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
            $user->notify(new WalletBalanceAddedNotification(
                $transaction->amount,
                'topup_request_approved',
                $transaction->id
            ));
        });
    }

    /**
     * Execute wallet withdrawal request
     */
    public function approveWithdrawal(WalletTransaction $transaction, User $admin): void
    {
        DB::transaction(function () use ($transaction, $admin) {
            $transaction = WalletTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== WalletTransaction::TYPE_WITHDRAW_REQUEST) {
                throw new \Exception('Transaction is not a withdrawal request');
            }

            if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
                throw new \Exception('Transaction is not pending');
            }

            $user = User::query()->whereKey($transaction->user_id)->lockForUpdate()->firstOrFail();
            abort_unless($user->points_balance >= $transaction->amount, 422, 'رصيد المستخدم غير كافٍ لتنفيذ طلب السحب');

            $transaction->status = WalletTransaction::STATUS_APPROVED;
            $transaction->processed_by = $admin->id;
            $transaction->processed_at = now();
            $transaction->save();

            $user->decrement('points_balance', $transaction->amount);
            $user->notify(new WalletWithdrawalProcessedNotification($transaction, true));
        });
    }

    /**
     * Admin adjustment (credit) directly to wallet
     */
    public function addAdjustment(User $user, int $amount, User $admin, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $admin, $notes) {
            $txn = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => WalletTransaction::TYPE_ADJUSTMENT,
                'status' => WalletTransaction::STATUS_APPROVED,
                'notes' => $notes,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $user->increment('points_balance', $amount);
            $user->notify(new WalletBalanceAddedNotification(
                $amount,
                'admin_adjustment',
                $txn->id
            ));

            return $txn;
        });
    }

    /**
     * Deduct amount from wallet (debit)
     * Creates a payment transaction and deducts from balance
     *
     * @param User $user The user to deduct from
     * @param int $amount Amount to deduct (in major units, e.g., 100 = 100.00)
     * @param string|null $notes Optional notes for the transaction
     * @param string|null $reference Optional reference ID (e.g., payment_id, order_id)
     * @return WalletTransaction
     * @throws \Exception If insufficient balance
     */
    public function deduct(User $user, int $amount, ?string $notes = null, ?string $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $notes, $reference) {
            // Check wallet balance
            abort_unless($user->points_balance >= $amount, 422, 'Insufficient wallet balance');

            // Create wallet transaction record
            $txn = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => WalletTransaction::TYPE_PAYMENT,
                'status' => WalletTransaction::STATUS_APPROVED,
                'notes' => $notes ?? ($reference ? "Payment: {$reference}" : 'Wallet deduction'),
                'processed_at' => now(),
            ]);

            // Deduct from wallet balance
            $user->decrement('points_balance', $amount);

            return $txn;
        });
    }

    /**
     * Admin adjustment (debit) directly from wallet
     * Similar to deduct but explicitly marked as admin adjustment
     */
    public function deductAdjustment(User $user, int $amount, User $admin, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $admin, $notes) {
            // Check wallet balance
            abort_unless($user->points_balance >= $amount, 422, 'Insufficient wallet balance');

            $txn = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => WalletTransaction::TYPE_ADJUSTMENT,
                'status' => WalletTransaction::STATUS_APPROVED,
                'notes' => $notes ?? 'Admin adjustment (debit)',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $user->decrement('points_balance', $amount);

            return $txn;
        });
    }

    /**
     * Set wallet balance directly (admin edit)
     */
    public function setBalance(User $user, int $newBalance, User $admin, ?string $notes = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $newBalance, $admin, $notes) {
            $oldBalance = $user->points_balance;
            $difference = $newBalance - $oldBalance;

            if ($difference === 0) {
                // No change needed
                return WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => 0,
                    'type' => WalletTransaction::TYPE_ADJUSTMENT,
                    'status' => WalletTransaction::STATUS_APPROVED,
                    'notes' => $notes ?? 'لا يوجد تغيير في الرصيد',
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                ]);
            }

            $txn = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => abs($difference),
                'type' => WalletTransaction::TYPE_ADJUSTMENT,
                'status' => WalletTransaction::STATUS_APPROVED,
                'notes' => $notes ?? "تعديل الرصيد من {$oldBalance} إلى {$newBalance}",
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $user->points_balance = $newBalance;
            $user->save();

            if ($difference > 0) {
                $user->notify(new WalletBalanceAddedNotification(
                    $difference,
                    'admin_balance_update',
                    $txn->id
                ));
            }

            return $txn;
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
     * Reject wallet withdrawal request
     */
    public function rejectWithdrawal(WalletTransaction $transaction, User $admin, string $reason): void
    {
        if ($transaction->type !== WalletTransaction::TYPE_WITHDRAW_REQUEST) {
            throw new \Exception('Transaction is not a withdrawal request');
        }

        if ($transaction->status !== WalletTransaction::STATUS_PENDING) {
            throw new \Exception('Transaction is not pending');
        }

        $transaction->status = WalletTransaction::STATUS_REJECTED;
        $transaction->processed_by = $admin->id;
        $transaction->processed_at = now();
        $transaction->rejection_reason = $reason;
        $transaction->save();

        $transaction->user?->notify(new WalletWithdrawalProcessedNotification($transaction, false));
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

    /**
     * Calculate balance from transactions (for verification)
     * This sums all approved transactions to verify the stored balance
     *
     * Note: Wallet payments (via PaymentService::payWithWallet) don't create
     * WalletTransaction records - they only deduct from points_balance directly.
     * So this calculation may not match if there were wallet payments.
     */
    public function calculateBalanceFromTransactions(User $user): int
    {
        $approvedTransactions = WalletTransaction::where('user_id', $user->id)
            ->where('status', WalletTransaction::STATUS_APPROVED)
            ->get();

        $calculatedBalance = 0;
        foreach ($approvedTransactions as $transaction) {
            // Top-up requests and adjustments add to balance
            if (in_array($transaction->type, [
                WalletTransaction::TYPE_TOPUP_REQUEST,
                WalletTransaction::TYPE_ADJUSTMENT,
            ])) {
                $calculatedBalance += $transaction->amount;
            }
            // Payments subtract from balance (if they exist in wallet_transactions)
            elseif ($transaction->type === WalletTransaction::TYPE_PAYMENT) {
                $calculatedBalance -= $transaction->amount;
            }
            // Executed withdrawals subtract from balance
            elseif ($transaction->type === WalletTransaction::TYPE_WITHDRAW_REQUEST) {
                $calculatedBalance -= $transaction->amount;
            }
            // Refunds add back to balance
            elseif ($transaction->type === WalletTransaction::TYPE_REFUND) {
                $calculatedBalance += $transaction->amount;
            }
        }

        // Also subtract wallet payments from Payment model (payment_method = 'wallet')
        $walletPayments = \App\Models\Payment::where('user_id', $user->id)
            ->where('payment_method', 'wallet')
            ->where('status', \App\Models\Payment::STATUS_SUCCEEDED)
            ->get();

        foreach ($walletPayments as $payment) {
            // Payment amount is stored in minor units, convert to major
            $amount = $payment->amount_minor / 100;
            $calculatedBalance -= $amount;
        }

        return $calculatedBalance;
    }

    /**
     * Verify wallet balance matches calculated balance from transactions
     * Returns true if balance matches, false if there's a discrepancy
     */
    public function verifyBalance(User $user): bool
    {
        $storedBalance = $user->points_balance;
        $calculatedBalance = $this->calculateBalanceFromTransactions($user);

        return $storedBalance === $calculatedBalance;
    }
}
