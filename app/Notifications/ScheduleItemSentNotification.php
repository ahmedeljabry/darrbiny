<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserScheduleProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleItemSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public UserScheduleProgress $scheduleProgress
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $userRequest = $this->scheduleProgress->userRequest;
        $planScheduleItem = $this->scheduleProgress->planScheduleItem;
        
        return [
            'type' => 'schedule_item_sent',
            'user_request_id' => $userRequest->id,
            'schedule_progress_id' => $this->scheduleProgress->id,
            'day_number' => $this->scheduleProgress->day_number,
            'title' => $planScheduleItem->title ?? "اليوم {$this->scheduleProgress->day_number}",
            'message' => "أرسل المدرب جدول المتابعة لليوم {$this->scheduleProgress->day_number}",
        ];
    }
}

