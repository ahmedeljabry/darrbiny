<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrainerOffer;
use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainerOfferSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TrainerOffer $offer,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            \App\Notifications\Channels\FcmChannel::class => 'sync',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $userRequest = $this->offer->relationLoaded('userRequest')
            ? $this->offer->userRequest
            : $this->offer->userRequest()->first();

        $trainer = $this->offer->relationLoaded('trainer')
            ? $this->offer->trainer
            : $this->offer->trainer()->first();

        return [
            'type' => 'trainer_offer_sent',
            'title' => 'عرض سعر جديد',
            'message' => 'قام احد المدربات بإرسال عرض سعر لكي',
            ...$this->offerPayload($userRequest),
            'trainer_id' => $this->offer->trainer_id,
            'trainer_name' => $trainer?->name,
        ];
    }

    private function offerPayload(?UserRequest $userRequest): array
    {
        return [
            'offer_id' => $this->offer->id,
            'user_request_id' => $this->offer->user_request_id,
            'order_number' => $userRequest?->order_number,
            'formatted_order_number' => $userRequest?->formatted_order_number,
            'display_order_number' => $userRequest?->display_order_number,
            'price_minor' => $this->offer->price_minor,
            'status' => $this->offer->status,
        ];
    }
}
