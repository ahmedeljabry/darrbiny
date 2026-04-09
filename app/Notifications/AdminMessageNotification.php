<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly bool $databaseOnly = false,
    ) {}

    public function via(object $notifiable): array
    {
        if ($this->databaseOnly) {
            return ['database'];
        }

        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
