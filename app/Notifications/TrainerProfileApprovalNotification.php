<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TrainerProfileApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ?User $admin,
        public readonly bool $approved,
        public readonly ?string $rejectionReason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'trainer_profile_approval',
            'status' => $this->approved ? 'approved' : 'rejected',
            'title' => $this->approved ? 'تمت الموافقة على ملفك' : 'تم رفض تعديلات ملفك',
            'message' => $this->approved
                ? 'تمت الموافقة على بيانات حسابك من قبل الإدارة ويمكنك استخدام الحساب الآن.'
                : 'تم رفض تعديلات حسابك من قبل الإدارة. يرجى مراجعة السبب وإعادة التعديل.',
            'rejection_reason' => $this->rejectionReason,
            'admin_id' => $this->admin?->id,
            'admin_name' => $this->admin?->name,
            'processed_at' => now()->toIso8601String(),
        ];
    }
}
