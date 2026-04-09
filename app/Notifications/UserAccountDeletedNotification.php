<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserAccountDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user
    ) {}

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'user_account_deleted',
            'title' => 'حذف حساب مستخدم',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_phone' => $this->user->phone_with_cc,
            'user_email' => $this->user->email,
            'message' => "تم حذف حساب المستخدم {$this->user->name}",
        ];
    }
}
