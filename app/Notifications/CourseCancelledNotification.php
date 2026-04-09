<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly UserRequest $userRequest,
        public readonly string $reason,
        public readonly int|float $refundAmount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $planTitle = $this->userRequest->plan?->title;
        $title = 'تم إلغاء الدورة';
        $message = $planTitle
            ? "تم إلغاء دورة {$planTitle} رقم #{$this->userRequest->id}"
            : "تم إلغاء الدورة رقم #{$this->userRequest->id}";

        if ($this->reason !== '') {
            $message .= "، السبب: {$this->reason}";
        }

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'course_cancelled',
            'user_request_id' => $this->userRequest->id,
            'plan_title' => $planTitle,
            'reason' => $this->reason,
            'refund_amount' => $this->refundAmount > 0 ? round((float) $this->refundAmount, 2) : null,
        ];
    }
}
