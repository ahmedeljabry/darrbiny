<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Modules\Support\Http\Requests\CreateTicketRequest;
use App\Modules\Support\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class SupportTicketController extends BaseController
{
    public function __construct(private readonly SupportTicketService $service) {}

    /**
     * Create a new support ticket
     */
    public function store(CreateTicketRequest $request)
    {
        $user = $request->user(); // Can be null for unauthenticated users

        $ticket = $this->service->createTicket($request->validated(), $user);

        return response()->json([
            'message' => 'تم إرسال تذكرة الدعم بنجاح',
            'data' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get user's tickets (if authenticated)
     */
    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $tickets = \App\Models\SupportTicket::where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->orWhere('phone_with_cc', $request->user()->phone_with_cc)
            ->with('messages')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at?->toIso8601String(),
                    'messages_count' => $ticket->messages->count(),
                ];
            }),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Get ticket details
     */
    public function show(Request $request, string $id)
    {
        $ticket = \App\Models\SupportTicket::with('messages.user')->findOrFail($id);

        // If authenticated, verify ownership
        if ($request->user()) {
            $user = $request->user();
            if ($ticket->user_id !== $user->id && 
                $ticket->email !== $user->email && 
                $ticket->phone_with_cc !== $user->phone_with_cc) {
                abort(403, 'Unauthorized');
            }
        }

        return response()->json([
            'data' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'name' => $ticket->name,
                'phone_with_cc' => $ticket->phone_with_cc,
                'email' => $ticket->email,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'messages' => $ticket->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'message' => $message->message,
                        'author_type' => $message->author_type,
                        'user_name' => $message->user?->name,
                        'created_at' => $message->created_at?->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }
}

