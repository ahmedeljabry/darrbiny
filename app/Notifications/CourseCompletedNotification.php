<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly UserRequest $userRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $planTitle = $this->userRequest->plan?->title;
        $orderNumber = $this->userRequest->display_order_number;
        $message = $planTitle
            ? "تم إكمال دورة {$planTitle} رقم #{$orderNumber}"
            : "تم إكمال الدورة رقم #{$orderNumber}";

        return [
            'title' => 'تم إكمال الدورة',
            'message' => $message,
            'type' => 'course_completed',
            'user_request_id' => $this->userRequest->id,
            'order_number' => $this->userRequest->order_number,
            'formatted_order_number' => $this->userRequest->formatted_order_number,
            'display_order_number' => $orderNumber,
            'plan_title' => $planTitle,
            'status' => UserRequest::STATUS_COMPLETED,
        ];
    }
}
