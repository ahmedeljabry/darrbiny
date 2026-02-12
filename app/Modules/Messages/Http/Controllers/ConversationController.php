<?php

declare(strict_types=1);

namespace App\Modules\Messages\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class ConversationController extends BaseController
{
    public function __construct(private readonly ConversationService $service) {}

    /**
     * List user's conversations
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->query('q'),
            'per_page' => $request->query('per_page', 20),
        ];

        $conversations = $this->service->getConversationsForUser($request->user(), $filters);
        return \App\Modules\Messages\Http\Resources\ConversationResource::collection($conversations)->response();
    }

    /**
     * Create (or revive) a conversation with another user
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
        ]);

        $authUser = $request->user();
        if ((string) $data['user_id'] === (string) $authUser->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot create a conversation with yourself.'],
            ]);
        }

        $otherUser = User::findOrFail($data['user_id']);

        $conversation = $this->service->findOrCreateConversation($authUser, $otherUser);
        $wasCreated = $conversation->wasRecentlyCreated;

        // Revive conversation visibility for both participants
        $conversation->user_one_deleted_at = null;
        $conversation->user_two_deleted_at = null;
        $conversation->save();

        $conversation->load([
            'userOne:id,name,phone_with_cc,profile_picture_id',
            'userOne.profilePicture',
            'userTwo:id,name,phone_with_cc,profile_picture_id',
            'userTwo.profilePicture',
            'messages' => fn ($q) => $q->latest()->limit(1),
        ]);

        return response()->json([
            'data' => new \App\Modules\Messages\Http\Resources\ConversationResource($conversation),
        ], $wasCreated ? 201 : 200);
    }

    /**
     * Delete conversation for user
     */
    public function destroy(Request $request, string $id)
    {
        $conversation = Conversation::forUser($request->user())->findOrFail($id);

        if ($conversation->isDeletedBy($request->user())) {
            abort(404, 'Conversation not found');
        }

        $this->service->deleteConversationForUser($conversation, $request->user());

        return response()->json(['message' => 'Conversation deleted successfully'], 200);
    }
}
