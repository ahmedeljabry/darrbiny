<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageService
{
    /**
     * Send a message in a conversation
     */
    public function sendMessage(Conversation $conversation, User $sender, string $message): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $message) {
            if ($conversation->user_one_id !== $sender->id && $conversation->user_two_id !== $sender->id) {
                throw new \Exception('User is not a participant in this conversation');
            }

            $messageModel = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message' => $message,
                'is_read' => false,
            ]);

            $conversation->last_message_at = now();
            $conversation->save();

            return $messageModel;
        });
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(Message $message, User $user): void
    {
        if ($message->sender_id !== $user->id && !$message->is_read) {
            $message->is_read = true;
            $message->read_at = now();
            $message->save();
        }
    }

    /**
     * Mark all messages in a conversation as read for a user
     */
    public function markConversationAsRead(Conversation $conversation, User $user): void
    {
        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            throw new \Exception('User is not a participant in this conversation');
        }

        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Search messages for a user
     */
    public function searchMessages(User $user, string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Message::with([
            'conversation',
            'sender:id,name,phone_with_cc,profile_picture_id',
            'sender.profilePicture',
        ])
        ->whereHas('conversation', function ($q) use ($user) {
            $q->forUser($user)
                ->where(function ($subQ) use ($user) {
                    $subQ->where(function ($w) use ($user) {
                        $w->where('user_one_id', $user->id)
                            ->whereNull('user_one_deleted_at');
                    })
                        ->orWhere(function ($w) use ($user) {
                            $w->where('user_two_id', $user->id)
                                ->whereNull('user_two_deleted_at');
                        });
                });
        })
        ->search($query)
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }

    /**
     * Get messages for a conversation (paginated)
     */
    public function getMessagesForConversation(Conversation $conversation, User $user, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            throw new \Exception('User is not a participant in this conversation');
        }

        return Message::where('conversation_id', $conversation->id)
            ->with(['sender:id,name,phone_with_cc,profile_picture_id', 'sender.profilePicture'])
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'page', $page);
    }
}
