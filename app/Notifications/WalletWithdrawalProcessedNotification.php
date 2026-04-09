<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletWithdrawalProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly WalletTransaction $transaction,
        public readonly bool $approved,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'wallet_withdraw_processed',
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'status' => $this->transaction->status,
            'rejection_reason' => $this->transaction->rejection_reason,
            'title' => $this->approved ? 'تم تنفيذ طلب السحب' : 'تم رفض طلب السحب',
            'message' => $this->approved
                ? "تم تنفيذ طلب سحب {$this->transaction->amount} من محفظتك"
                : "تم رفض طلب سحب {$this->transaction->amount} من محفظتك",
        ];
    }
}
