<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'support_ticket_created',
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->ticket->user_id,
            'name' => $this->ticket->name,
            'phone' => $this->ticket->phone_with_cc,
            'email' => $this->ticket->email,
            'subject' => $this->ticket->subject,
            'message' => "تم إنشاء تذكرة دعم جديدة من {$this->ticket->name}",
        ];
    }
}

