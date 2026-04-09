<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TrainerProfileUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $trainer,
        public readonly TrainerProfile $profile,
        public readonly array $changes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $changedFields = implode(', ', array_keys($this->changes));

        return [
            'title' => 'طلب موافقة على تعديلات ملف المدرب',
            'message' => "المدرب {$this->trainer->name} قام بتعديل بياناته ({$changedFields}) ويحتاج موافقة",
            'type' => 'trainer_profile_update',
            'trainer_id' => $this->trainer->id,
            'trainer_name' => $this->trainer->name,
            'profile_id' => $this->profile->id,
            'changes' => $this->changes,
            'pending_changes' => $this->profile->pending_changes,
        ];
    }
}
