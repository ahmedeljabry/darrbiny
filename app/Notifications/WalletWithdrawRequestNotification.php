<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Support\WalletAmount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletWithdrawRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WalletTransaction $transaction
    ) {}

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $user = $this->transaction->user;
        $amountMinor = $this->transaction->amountMinor();
        $formattedAmount = WalletAmount::formatMinor($amountMinor, 2);

        return [
            'type' => 'wallet_withdraw_request',
            'transaction_id' => $this->transaction->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_phone' => $user->phone_with_cc,
            'amount' => $this->transaction->amountMajor(),
            'amount_minor' => $amountMinor,
            'title' => 'طلب سحب من المحفظة',
            'message' => "طلب سحب {$formattedAmount} من محفظة المستخدم {$user->name}",
        ];
    }
}
