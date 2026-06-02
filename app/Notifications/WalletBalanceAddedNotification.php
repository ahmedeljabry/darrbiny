<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserRequest;
use App\Support\WalletAmount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletBalanceAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $amountMinor,
        public readonly string $reason = 'wallet_credit',
        public readonly ?string $transactionId = null,
        public readonly ?UserRequest $userRequest = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $formattedAmount = WalletAmount::formatMinor($this->amountMinor, 2);
        $isCoursePayout = $this->reason === 'course_payout' && $this->userRequest instanceof UserRequest;
        $orderNumber = $isCoursePayout ? $this->userRequest->display_order_number : null;

        return [
            'type' => 'wallet_balance_added',
            'amount' => WalletAmount::minorToMajor($this->amountMinor),
            'amount_minor' => $this->amountMinor,
            'reason' => $this->reason,
            'transaction_id' => $this->transactionId,
            'user_request_id' => $this->userRequest?->id,
            'order_number' => $this->userRequest?->order_number,
            'formatted_order_number' => $this->userRequest?->formatted_order_number,
            'display_order_number' => $orderNumber,
            'title' => $isCoursePayout ? 'تم إضافة مستحقات الكورس إلى محفظتك' : 'تم إضافة رصيد إلى محفظتك',
            'message' => $isCoursePayout
                ? "تم إضافة مستحقات الكورس رقم #{$orderNumber} إلى محفظتك"
                : "تم إضافة {$formattedAmount} إلى محفظتك",
        ];
    }
}
