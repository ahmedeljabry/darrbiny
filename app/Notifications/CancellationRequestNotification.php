<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CancellationRequest;
use App\Support\WalletAmount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CancellationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CancellationRequest $cancellationRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $userRequest = $this->cancellationRequest->userRequest;
        $user = $this->cancellationRequest->user;
        $status = $this->cancellationRequest->status;
        $orderNumber = $userRequest?->notificationOrderNumber();

        $title = match ($status) {
            'approved' => 'تم قبول طلب الإلغاء',
            'rejected' => 'تم رفض طلب الإلغاء',
            default => 'طلب إلغاء دورة تدريبية',
        };

        $message = match ($status) {
            'approved' => "تم قبول طلب إلغاء الدورة رقم #{$orderNumber} وتم إرجاع المبلغ إلى محفظتك",
            'rejected' => "تم رفض طلب إلغاء الدورة رقم #{$orderNumber}",
            default => "طلب المستخدم {$user->name} إلغاء الدورة رقم #{$orderNumber}",
        };

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'cancellation_request',
            'cancellation_request_id' => $this->cancellationRequest->id,
            'user_request_id' => $userRequest->id,
            'order_number' => $userRequest->order_number,
            'formatted_order_number' => $userRequest->formatted_order_number,
            'display_order_number' => $orderNumber,
            'user_id' => $user->id,
            'status' => $status,
            'reason' => $this->cancellationRequest->reason,
            'refund_amount' => $status === 'approved' && (int) $this->cancellationRequest->refund_amount_minor > 0
                ? WalletAmount::minorToMajor((int) $this->cancellationRequest->refund_amount_minor)
                : null,
        ];
    }
}
