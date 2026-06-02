<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Support\WalletAmount;
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
        $amountMinor = $this->transaction->amountMinor();
        $formattedAmount = WalletAmount::formatMinor($amountMinor, 2);
        $isTrainer = method_exists($notifiable, 'isTrainerAccount') && $notifiable->isTrainerAccount();
        $approvedMessage = $isTrainer
            ? 'تم تحويل مستحقاتك إلى الحساب البنكي'
            : 'تم تحويل قيمة الكورس إلى حسابك البنكي';

        return [
            'type' => 'wallet_withdraw_processed',
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amountMajor(),
            'amount_minor' => $amountMinor,
            'status' => $this->transaction->status,
            'rejection_reason' => $this->transaction->rejection_reason,
            'title' => $this->approved ? 'تم تنفيذ طلب السحب' : 'تم رفض طلب السحب',
            'message' => $this->approved
                ? $approvedMessage
                : "تم رفض طلب سحب {$formattedAmount} من محفظتك",
            'withdrawal_recipient_type' => $isTrainer ? 'trainer' : 'student',
        ];
    }
}
