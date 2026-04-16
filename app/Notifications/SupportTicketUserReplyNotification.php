<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketUserReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketMessage $ticketMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $userName = $this->ticketMessage->user?->name
            ?? $this->ticket->user?->name
            ?? $this->ticket->name
            ?? 'مستخدم';

        return [
            'type' => 'support_ticket_user_reply',
            'ticket_id' => $this->ticket->id,
            'ticket_message_id' => $this->ticketMessage->id,
            'user_id' => $this->ticketMessage->user_id ?? $this->ticket->user_id,
            'user_name' => $userName,
            'subject' => $this->ticket->subject,
            'title' => 'رد جديد على تذكرة الدعم',
            'message' => "قام {$userName} بالرد على التذكرة: {$this->ticket->subject}",
        ];
    }
}
