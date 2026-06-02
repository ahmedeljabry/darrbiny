<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserScheduleProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleItemSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public UserScheduleProgress $scheduleProgress
    ) {}

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $userRequest = $this->scheduleProgress->userRequest;
        $planScheduleItem = $this->scheduleProgress->planScheduleItem;
        $orderNumber = $userRequest->notificationOrderNumber();

        return [
            'type' => 'schedule_item_sent',
            'user_request_id' => $userRequest->id,
            'order_number' => $userRequest->order_number,
            'formatted_order_number' => $userRequest->formatted_order_number,
            'display_order_number' => $orderNumber,
            'schedule_progress_id' => $this->scheduleProgress->id,
            'day_number' => $this->scheduleProgress->day_number,
            'title' => $planScheduleItem->title ?? "اليوم {$this->scheduleProgress->day_number}",
            'message' => "أرسل المدرب جدول المتابعة لليوم {$this->scheduleProgress->day_number}",
        ];
    }
}
