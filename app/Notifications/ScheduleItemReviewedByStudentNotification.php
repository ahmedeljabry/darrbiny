<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleItemReviewedByStudentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly UserScheduleProgress $scheduleProgress
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->scheduleProgress->loadMissing(['userRequest.user', 'planScheduleItem']);

        $userRequest = $this->scheduleProgress->userRequest;
        $status = (string) $this->scheduleProgress->status;
        $accepted = $status === UserScheduleProgress::STATUS_ACCEPTED;
        $dayNumber = (int) $this->scheduleProgress->day_number;
        $message = $accepted
            ? 'تم استلام الطالبة لمرحلة اليوم'
            : 'رفض استلام الطالبة لمرحلة اليوم';

        return [
            'type' => 'schedule_item_reviewed_by_student',
            'user_request_id' => $userRequest?->id,
            'schedule_progress_id' => $this->scheduleProgress->id,
            'day_number' => $dayNumber,
            'status' => $status,
            'rejection_reason' => $this->scheduleProgress->rejection_reason,
            'student_id' => $userRequest?->user_id,
            'student_name' => $userRequest?->user?->name,
            'order_number' => $userRequest?->order_number,
            'formatted_order_number' => $userRequest?->formatted_order_number,
            'display_order_number' => $userRequest instanceof UserRequest ? $userRequest->display_order_number : null,
            'title' => $message,
            'message' => $message,
        ];
    }
}
