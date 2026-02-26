<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TrainerRegistrationPendingApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly User $trainer) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'trainer_registration_pending_approval',
            'title' => 'طلب تنشيط مدرب جديد',
            'message' => "المدرب {$this->trainer->name} سجّل حسابًا جديدًا ويحتاج موافقة الإدارة.",
            'trainer_id' => $this->trainer->id,
            'trainer_name' => $this->trainer->name,
            'trainer_phone' => $this->trainer->phone_with_cc,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

