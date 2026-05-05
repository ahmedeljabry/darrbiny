<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrainerOffer;
use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TrainerOfferAcceptedNotification extends Notification implements ShouldQueue
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
            \App\Notifications\Channels\FcmChannel::class => config('queue.default'),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $userRequest = $this->offer->relationLoaded('userRequest')
            ? $this->offer->userRequest
            : $this->offer->userRequest()->with('user')->first();

        return [
            'type' => 'trainer_offer_accepted',
            'title' => 'تم قبول عرض السعر',
            'message' => 'قامت المتدربه بقبول عرض سعرك',
            ...$this->offerPayload($userRequest),
            'trainee_id' => $userRequest?->user_id,
            'trainee_name' => $userRequest?->user?->name,
        ];
    }

    private function offerPayload(?UserRequest $userRequest): array
    {
        return [
            'offer_id' => $this->offer->id,
            'user_request_id' => $this->offer->user_request_id,
            'order_number' => $userRequest?->order_number,
            'formatted_order_number' => $userRequest?->formatted_order_number,
            'price_minor' => $this->offer->price_minor,
            'status' => $this->offer->status,
        ];
    }
}
