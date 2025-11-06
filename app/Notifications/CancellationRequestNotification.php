<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CancellationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CancellationRequest $cancellationRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $userRequest = $this->cancellationRequest->userRequest;
        $user = $this->cancellationRequest->user;
        
        return [
            'title' => 'طلب إلغاء دورة تدريبية',
            'message' => "طلب المستخدم {$user->name} إلغاء الدورة رقم #{$userRequest->id}",
            'type' => 'cancellation_request',
            'cancellation_request_id' => $this->cancellationRequest->id,
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'reason' => $this->cancellationRequest->reason,
        ];
    }
}


