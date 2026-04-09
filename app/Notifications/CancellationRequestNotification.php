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

        $title = match ($status) {
            'approved' => 'تم قبول طلب الإلغاء',
            'rejected' => 'تم رفض طلب الإلغاء',
            default => 'طلب إلغاء دورة تدريبية',
        };

        $message = match ($status) {
            'approved' => "تم قبول طلب إلغاء الدورة رقم #{$userRequest->id} وتم إرجاع المبلغ إلى محفظتك",
            'rejected' => "تم رفض طلب إلغاء الدورة رقم #{$userRequest->id}",
            default => "طلب المستخدم {$user->name} إلغاء الدورة رقم #{$userRequest->id}",
        };

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'cancellation_request',
            'cancellation_request_id' => $this->cancellationRequest->id,
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'status' => $status,
            'reason' => $this->cancellationRequest->reason,
            'refund_amount' => $status === 'approved' && $userRequest->total_paid_minor > 0
                ? WalletAmount::minorToMajor((int) $userRequest->total_paid_minor)
                : null,
        ];
    }
}
