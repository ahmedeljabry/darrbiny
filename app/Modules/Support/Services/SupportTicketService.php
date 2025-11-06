<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SupportTicketService
{
    /**
     * Create a new support ticket
     */
    public function createTicket(array $data, ?User $user = null): SupportTicket
    {
        return DB::transaction(function () use ($data, $user) {
            $ticket = SupportTicket::create([
                'user_id' => $user?->id,
                'name' => $data['name'],
                'phone_with_cc' => $data['phone_with_cc'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'status' => 'open',
            ]);

            // Create the initial message with details
            SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user?->id,
                'author_type' => $user ? 'user' : 'user',
                'message' => $data['details'],
            ]);

            // Notify all admins
            $admins = User::role('admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new SupportTicketCreatedNotification($ticket));
            }

            return $ticket->load('messages');
        });
    }
}

