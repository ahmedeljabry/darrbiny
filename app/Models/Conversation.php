<?php

declare(strict_types=1);

namespace App\Models;

class Conversation extends BaseModel
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
        'user_one_deleted_at',
        'user_two_deleted_at',
        'version',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'user_one_deleted_at' => 'datetime',
        'user_two_deleted_at' => 'datetime',
        'version' => 'integer',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the other user in the conversation
     */
    public function otherUser(User $user): ?User
    {
        if ($this->user_one_id === $user->id) {
            return $this->userTwo;
        }
        if ($this->user_two_id === $user->id) {
            return $this->userOne;
        }
        return null;
    }

    /**
     * Get unread message count for a specific user
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Scope: Get conversations for a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)
              ->orWhere('user_two_id', $user->id);
        });
    }

    /**
     * Scope: Exclude conversations deleted by user
     */
    public function scopeNotDeletedBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            if ($this->user_one_id === $user->id) {
                $q->whereNull('user_one_deleted_at');
            } else {
                $q->whereNull('user_two_deleted_at');
            }
        });
    }

    /**
     * Check if conversation is deleted by user
     */
    public function isDeletedBy(User $user): bool
    {
        if ($this->user_one_id === $user->id) {
            return $this->user_one_deleted_at !== null;
        }
        if ($this->user_two_id === $user->id) {
            return $this->user_two_deleted_at !== null;
        }
        return false;
    }
}

