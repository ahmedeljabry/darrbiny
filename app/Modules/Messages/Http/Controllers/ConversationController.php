<?php

declare(strict_types=1);

namespace App\Modules\Messages\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

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
     * Get conversation details
     */
    public function show(Request $request, string $id)
    {
        $conversation = Conversation::with(['userOne', 'userTwo'])
            ->forUser($request->user())
            ->findOrFail($id);

        // Verify user is a participant and conversation is not deleted by them
        if ($conversation->isDeletedBy($request->user())) {
            abort(404, 'Conversation not found');
        }

        return response()->json([
            'data' => new \App\Modules\Messages\Http\Resources\ConversationResource($conversation),
        ]);
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

