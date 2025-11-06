<?php

declare(strict_types=1);

namespace App\Models;

class Message extends BaseModel
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'is_read',
        'read_at',
        'version',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'version' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }

    /**
     * Check if message is read by a specific user
     */
    public function isReadBy(User $user): bool
    {
        // Message is read if it's not from the user and is_read is true
        return $this->sender_id !== $user->id && $this->is_read;
    }

    /**
     * Scope: Get unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Search in message content
     */
    public function scopeSearch($query, string $searchQuery)
    {
        return $query->where('message', 'like', "%{$searchQuery}%");
    }
}

