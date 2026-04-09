<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RewardRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrizeRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RewardRedemption $redemption
    ) {}

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'prize_request',
            'title' => 'طلب جائزة جديد',
            'redemption_id' => $this->redemption->id,
            'user_id' => $this->redemption->user_id,
            'user_name' => $this->redemption->user->name,
            'points_spent' => $this->redemption->points_spent,
            'reward_title' => $this->redemption->reward->title ?? 'طلب جائزة',
            'message' => "طلب جديد للحصول على جائزة من {$this->redemption->user->name}",
        ];
    }
}
