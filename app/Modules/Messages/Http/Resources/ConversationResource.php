<?php

declare(strict_types=1);

namespace App\Modules\Messages\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $otherUser = $this->otherUser($user);
        $lastMessage = $this->messages->first();

        return [
            'id' => $this->id,
            'participant' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'phone' => $otherUser->phone_with_cc,
                'avatar' => $otherUser->profile_picture_url ?? null,
            ] : null,
            'last_message' => $lastMessage ? [
                'id' => $lastMessage->id,
                'message' => $lastMessage->message,
                'sender_id' => $lastMessage->sender_id,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
            ] : null,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => $this->unreadCountFor($user),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

