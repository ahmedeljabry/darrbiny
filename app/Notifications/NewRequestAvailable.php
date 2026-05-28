<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewRequestAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly UserRequest $request) {}

    public function via($notifiable): array
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

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'new_request_available',
            'title' => 'طلب تدريب جديد',
            'message' => 'يوجد طلب تدريب جديد في منطقتك',
            'request_id' => $this->request->id,
            'user_request_id' => $this->request->id,
            'order_number' => $this->request->order_number,
            'formatted_order_number' => $this->request->formatted_order_number,
            'display_order_number' => $this->request->display_order_number,
            'start_date' => optional($this->request->start_date)->toDateString(),
            'plan_title' => $this->request->plan?->title,
            'user_name' => $this->request->user?->name,
            'country_id' => $this->request->country_id,
            'area_level_1' => $this->request->area_level_1,
            'area_level_2' => $this->request->area_level_2,
            'area_level_3' => $this->request->area_level_3,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
