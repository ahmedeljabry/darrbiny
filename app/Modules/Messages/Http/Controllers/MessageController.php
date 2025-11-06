<?php

declare(strict_types=1);

namespace App\Modules\Messages\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class MessageController extends BaseController
{
    public function __construct(private readonly MessageService $service) {}

    /**
     * Get messages in a conversation
     */
    public function index(Request $request, string $conversationId)
    {
        $conversation = Conversation::forUser($request->user())->findOrFail($conversationId);

        if ($conversation->isDeletedBy($request->user())) {
            abort(404, 'Conversation not found');
        }

        $page = (int) $request->query('page', 1);
        $messages = $this->service->getMessagesForConversation($conversation, $request->user(), $page);

        return \App\Modules\Messages\Http\Resources\MessageResource::collection($messages)->response();
    }

    /**
     * Send a message
     */
    public function store(Request $request, string $conversationId)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = Conversation::forUser($request->user())->findOrFail($conversationId);

        if ($conversation->isDeletedBy($request->user())) {
            abort(404, 'Conversation not found');
        }

        $message = $this->service->sendMessage($conversation, $request->user(), $validated['message']);

        return response()->json([
            'data' => new \App\Modules\Messages\Http\Resources\MessageResource($message->load('sender')),
        ], 201);
    }

    /**
     * Mark all messages in conversation as read
     */
    public function markAllRead(Request $request, string $conversationId)
    {
        $conversation = Conversation::forUser($request->user())->findOrFail($conversationId);

        if ($conversation->isDeletedBy($request->user())) {
            abort(404, 'Conversation not found');
        }

        $this->service->markConversationAsRead($conversation, $request->user());

        return response()->json(['message' => 'All messages marked as read'], 200);
    }

    /**
     * Mark a single message as read
     */
    public function markRead(Request $request, string $id)
    {
        $message = Message::with('conversation')->findOrFail($id);
        $conversation = $message->conversation;

        // Verify user is a participant
        if ($conversation->user_one_id !== $request->user()->id && $conversation->user_two_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $this->service->markAsRead($message, $request->user());

        return response()->json([
            'data' => new \App\Modules\Messages\Http\Resources\MessageResource($message),
        ]);
    }

    /**
     * Search messages
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $messages = $this->service->searchMessages($request->user(), $request->query('q'));

        return \App\Modules\Messages\Http\Resources\MessageResource::collection($messages)->response();
    }
}

