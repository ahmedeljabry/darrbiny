<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    /**
     * Find or create a conversation between two users
     * Ensures user_one_id < user_two_id for consistency
     */
    public function findOrCreateConversation(User $user1, User $user2): Conversation
    {
        $userIds = [$user1->id, $user2->id];
        sort($userIds);

        return DB::transaction(function () use ($userIds) {
            $conversation = Conversation::where('user_one_id', $userIds[0])
                ->where('user_two_id', $userIds[1])
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_one_id' => $userIds[0],
                    'user_two_id' => $userIds[1],
                ]);
            }

            return $conversation;
        });
    }

    /**
     * Get conversations for a user with filters
     */
    public function getConversationsForUser(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Conversation::with(['userOne', 'userTwo', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->forUser($user)
        ->where(function ($q) use ($user) {
            $q->where(function ($subQ) use ($user) {
                $subQ->where('user_one_id', $user->id)
                     ->whereNull('user_one_deleted_at');
            })
            ->orWhere(function ($subQ) use ($user) {
                $subQ->where('user_two_id', $user->id)
                     ->whereNull('user_two_deleted_at');
            });
        });

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('messages', function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%");
            });
        }

        $query->orderBy('last_message_at', 'desc')->orderBy('created_at', 'desc');
        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Delete conversation for a specific user (soft delete)
     */
    public function deleteConversationForUser(Conversation $conversation, User $user): void
    {
        if ($conversation->user_one_id === $user->id) {
            $conversation->user_one_deleted_at = now();
        } elseif ($conversation->user_two_id === $user->id) {
            $conversation->user_two_deleted_at = now();
        } else {
            throw new \Exception('User is not a participant in this conversation');
        }

        $conversation->save();
    }

    /**
     * Get total unread message count for a user
     */
    public function getUnreadCountForUser(User $user): int
    {
        return Conversation::forUser($user)
            ->where(function ($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->whereNull('user_one_deleted_at')
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('user_two_id', $user->id)
                           ->whereNull('user_two_deleted_at');
                  });
            })
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                  ->where('is_read', false);
            }])
            ->get()
            ->sum('unread_count');
    }
}

