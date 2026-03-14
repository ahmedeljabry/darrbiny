<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketMessage $ticketMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'support_ticket_reply',
            'ticket_id' => $this->ticket->id,
            'ticket_message_id' => $this->ticketMessage->id,
            'subject' => $this->ticket->subject,
            'title' => 'رد جديد على تذكرة الدعم',
            'message' => "تم الرد على تذكرتك: {$this->ticket->subject}",
        ];
    }
}
