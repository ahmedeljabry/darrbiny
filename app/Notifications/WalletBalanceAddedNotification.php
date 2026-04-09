<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletBalanceAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $amount,
        public readonly string $reason = 'wallet_credit',
        public readonly ?string $transactionId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'wallet_balance_added',
            'amount' => $this->amount,
            'reason' => $this->reason,
            'transaction_id' => $this->transactionId,
            'title' => 'تم إضافة رصيد إلى محفظتك',
            'message' => "تم إضافة {$this->amount} إلى محفظتك",
        ];
    }
}
