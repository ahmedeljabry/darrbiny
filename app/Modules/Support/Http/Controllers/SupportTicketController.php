<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Modules\Support\Http\Requests\CreateTicketRequest;
use App\Modules\Support\Http\Requests\SendTicketMessageRequest;
use App\Modules\Support\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Laravel\Sanctum\PersonalAccessToken;

class SupportTicketController extends BaseController
{
    public function __construct(private readonly SupportTicketService $service) {}

    /**
     * Create a new support ticket
     */
    public function store(CreateTicketRequest $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

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
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $ticketsQuery = SupportTicket::query()
            ->visibleToUser($user)
            ->withCount('messages')
            ->latest();

        $tickets = $ticketsQuery->paginate(20);

        return response()->json([
            'data' => $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at?->toIso8601String(),
                    'messages_count' => (int) $ticket->messages_count,
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
        $ticket = SupportTicket::with('messages.user')->findOrFail($id);
        $this->authorizeTicketAccess($request, $ticket);

        $messages = $ticket->messages()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'name' => $ticket->name,
                'phone_with_cc' => $ticket->phone_with_cc,
                'email' => $ticket->email,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'messages' => $messages->map(fn (SupportTicketMessage $message) => $this->serializeMessage($message, $request)),
            ],
        ]);
    }

    /**
     * Get paginated ticket chat messages ordered by oldest-first.
     */
    public function messages(Request $request, string $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $this->authorizeTicketAccess($request, $ticket);

        $limit = max(1, min((int) $request->query('limit', 50), 100));
        $messages = SupportTicketMessage::where('ticket_id', $ticket->id)
            ->with('user:id,name')
            ->orderBy('created_at')
            ->paginate($limit)
            ->withQueryString();

        return response()->json([
            'data' => [
                'ticket' => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                ],
                'messages' => $messages->getCollection()
                    ->map(fn (SupportTicketMessage $message) => $this->serializeMessage($message, $request))
                    ->values(),
            ],
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Send a message inside a ticket thread.
     */
    public function sendMessage(SendTicketMessageRequest $request, string $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $this->authorizeTicketAccess($request, $ticket);

        $user = $request->user();
        $isAdmin = (bool) $user?->hasRole('ADMIN');
        $status = $isAdmin ? $request->input('status') : null;

        $message = $this->service->addMessage(
            $ticket,
            $user,
            (string) $request->input('message'),
            $status,
            $isAdmin
        );

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_status' => $ticket->fresh()->status,
                'message' => $this->serializeMessage($message, $request),
            ],
        ], 201);
    }

    private function authorizeTicketAccess(Request $request, SupportTicket $ticket): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }
        if ($user->hasRole('ADMIN')) {
            return;
        }
        $isOwner = $ticket->user_id === $user->id || $ticket->email === $user->email || $ticket->phone_with_cc === $user->phone_with_cc;
        abort_unless($isOwner, 403, 'Unauthorized');
    }

    private function serializeMessage(SupportTicketMessage $message, Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $message->id,
            'message' => $message->message,
            'author_type' => $message->author_type,
            'user_id' => $message->user_id,
            'user_name' => $message->user?->name,
            'is_mine' => $actor ? $message->user_id === $actor->id : false,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function resolveAuthenticatedUser(Request $request): ?User
    {
        $user = $request->user('sanctum') ?? $request->user();
        if ($user instanceof User) {
            return $user;
        }

        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }
}
