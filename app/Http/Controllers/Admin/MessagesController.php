<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class MessagesController extends BaseController
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $userId = $request->query('user_id');
        $unread = $request->boolean('unread');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Conversation::with(['userOne', 'userTwo', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }]);

        if ($search) {
            $query->whereHas('messages', function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%");
            });
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            });
        }

        if ($unread) {
            $query->whereHas('messages', function ($q) {
                $q->where('is_read', false);
            });
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'phone_with_cc']);

        return view('admin.messages.index', compact('conversations', 'users', 'search', 'userId', 'unread', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $conversation = Conversation::with(['userOne', 'userTwo', 'messages.sender'])
            ->findOrFail($id);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return view('admin.messages.show', compact('conversation', 'messages'));
    }

    public function messages(Request $request)
    {
        $search = $request->query('q');
        $userId = $request->query('user_id');
        $senderId = $request->query('sender_id');
        $unread = $request->boolean('unread');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Message::with(['conversation.userOne', 'conversation.userTwo', 'sender']);

        if ($search) {
            $query->where('message', 'like', "%{$search}%");
        }

        if ($userId) {
            $query->whereHas('conversation', function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            });
        }

        if ($senderId) {
            $query->where('sender_id', $senderId);
        }

        if ($unread) {
            $query->where('is_read', false);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'phone_with_cc']);

        return view('admin.messages.messages', compact('messages', 'users', 'search', 'userId', 'senderId', 'unread', 'dateFrom', 'dateTo'));
    }
}

