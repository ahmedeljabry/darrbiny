<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketReplyNotification;
use App\Notifications\SupportTicketUserReplyNotification;
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
            $admins = User::role('ADMIN')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new SupportTicketCreatedNotification($ticket));
            }

            return $ticket->load('messages');
        });
    }

    /**
     * Add a message to an existing support ticket.
     * If a user replies to a closed ticket, it reopens automatically.
     * Admin can optionally update ticket status with the reply.
     */
    public function addMessage(
        SupportTicket $ticket,
        User $actor,
        string $message,
        ?string $status = null,
        bool $isAdmin = false
    ): SupportTicketMessage
    {
        return DB::transaction(function () use ($ticket, $actor, $message, $status, $isAdmin) {
            $ticketMessage = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $actor->id,
                'author_type' => $isAdmin ? 'admin' : 'user',
                'message' => $message,
            ]);

            if ($isAdmin) {
                if ($status !== null && in_array($status, ['open', 'pending', 'closed'], true)) {
                    $ticket->status = $status;
                    $ticket->save();
                }
            } elseif ($ticket->status === 'closed') {
                $ticket->status = 'open';
                $ticket->save();
            }

            if (!$ticket->user_id && !$isAdmin) {
                $ticket->user_id = $actor->id;
                $ticket->save();
            }

            if ($isAdmin) {
                $ticketOwner = $this->resolveTicketOwner($ticket);

                if ($ticketOwner && $ticket->user_id !== $ticketOwner->id) {
                    $ticket->user_id = $ticketOwner->id;
                    $ticket->save();
                }

                if ($ticketOwner && $ticketOwner->id !== $actor->id) {
                    $ticketOwner->notify(new SupportTicketReplyNotification($ticket, $ticketMessage));
                }
            } else {
                $admins = User::role('ADMIN')->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new SupportTicketUserReplyNotification($ticket, $ticketMessage));
                }
            }

            return $ticketMessage->load('user');
        });
    }

    private function resolveTicketOwner(SupportTicket $ticket): ?User
    {
        if ($ticket->user_id) {
            return User::find($ticket->user_id);
        }

        $messageOwnerId = $ticket->messages()
            ->where('author_type', 'user')
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->value('user_id');

        if ($messageOwnerId) {
            return User::find($messageOwnerId);
        }

        return User::query()
            ->where(function ($query) use ($ticket): void {
                if (filled($ticket->email)) {
                    $query->orWhere('email', $ticket->email);
                }

                if (filled($ticket->phone_with_cc)) {
                    $query->orWhere('phone_with_cc', $ticket->phone_with_cc);
                }
            })
            ->first();
    }
}
