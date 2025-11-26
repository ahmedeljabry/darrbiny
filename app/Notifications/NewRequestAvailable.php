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
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'طلب جديد متاح',
            'message' => sprintf('هناك طلب جديد لباقة %s', $this->request->plan?->title ?? ''),
            'request_id' => $this->request->id,
            'start_date' => optional($this->request->start_date)->toDateString(),
            'plan_title' => $this->request->plan?->title,
            'user_name' => $this->request->user?->name,
        ];
    }
}
