<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletTopupRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WalletTransaction $transaction
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $user = $this->transaction->user;
        
        return [
            'type' => 'wallet_topup_request',
            'transaction_id' => $this->transaction->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_phone' => $user->phone_with_cc,
            'amount' => $this->transaction->amount,
            'message' => "طلب إضافة {$this->transaction->amount} إلى محفظة المستخدم {$user->name}",
        ];
    }
}

