<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralPointsAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $points,
        public readonly ?string $sourceUserName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = $this->sourceUserName
            ? "تم إضافة {$this->points} نقطة من اشتراك {$this->sourceUserName}"
            : "تم إضافة {$this->points} نقطة إلى رصيد المكافآت";

        return [
            'type' => 'referral_points_added',
            'points' => $this->points,
            'source_user_name' => $this->sourceUserName,
            'title' => 'تم إضافة نقاط إلى مكافآتي',
            'message' => $message,
        ];
    }
}
