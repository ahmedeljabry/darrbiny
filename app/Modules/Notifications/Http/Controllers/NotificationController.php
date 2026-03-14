<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Controllers;

use App\Models\Message;
use App\Notifications\CancellationRequestNotification;
use App\Notifications\CourseCancelledNotification;
use App\Notifications\NewRequestAvailable;
use App\Notifications\ReferralPointsAddedNotification;
use App\Notifications\ScheduleItemSentNotification;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketReplyNotification;
use App\Notifications\WalletBalanceAddedNotification;
use App\Notifications\WalletWithdrawalProcessedNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;

class NotificationController extends BaseController
{
    private const TRAINING_NOTIFICATION_TYPES = [
        ScheduleItemSentNotification::class,
        NewRequestAvailable::class,
        CancellationRequestNotification::class,
        CourseCancelledNotification::class,
    ];

    private const SUPPORT_NOTIFICATION_TYPES = [
        SupportTicketReplyNotification::class,
        SupportTicketCreatedNotification::class,
    ];

    private const REWARDS_NOTIFICATION_TYPES = [
        ReferralPointsAddedNotification::class,
    ];

    private const WALLET_NOTIFICATION_TYPES = [
        WalletBalanceAddedNotification::class,
        WalletWithdrawalProcessedNotification::class,
    ];

    /**
     * Get user notifications with pagination
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 20);
        $read = $request->query('read'); 
        $type = $request->query('type');

        $query = $user->notifications();

        if ($read === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($read === 'unread') {
            $query->whereNull('read_at');
        }

        // Filter by type
        if ($type) {
            $query->where('type', 'like', "%{$type}%");
        }

        $notifications = $query->latest()->paginate($limit);

        return response()->json([
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Get unread badge counters for app sections
     */
    public function badges(Request $request)
    {
        $user = $request->user();

        $unreadNotifications = $user->unreadNotifications()
            ->select('id', 'type', 'data')
            ->get();

        $notificationsCount = $unreadNotifications->count();
        $messagesCount = $this->unreadMessagesCountForUser($user->id);
        $trainingsCount = $this->countByTypes($unreadNotifications, self::TRAINING_NOTIFICATION_TYPES);
        $supportTicketsCount = $this->countByTypes($unreadNotifications, self::SUPPORT_NOTIFICATION_TYPES);
        $walletCount = $this->countWalletNotifications($unreadNotifications);
        $rewardsCount = $this->countByTypes($unreadNotifications, self::REWARDS_NOTIFICATION_TYPES);
        $accountCount = $supportTicketsCount + $walletCount + $rewardsCount;

        return response()->json([
            'data' => [
                'notifications' => [
                    'count' => $notificationsCount,
                    'has_unread' => $notificationsCount > 0,
                ],
                'messages' => [
                    'count' => $messagesCount,
                    'has_unread' => $messagesCount > 0,
                ],
                'trainings' => [
                    'count' => $trainingsCount,
                    'has_unread' => $trainingsCount > 0,
                ],
                'support_tickets' => [
                    'count' => $supportTicketsCount,
                    'has_unread' => $supportTicketsCount > 0,
                ],
                'wallet' => [
                    'count' => $walletCount,
                    'has_unread' => $walletCount > 0,
                ],
                'rewards' => [
                    'count' => $rewardsCount,
                    'has_unread' => $rewardsCount > 0,
                ],
                'account' => [
                    'count' => $accountCount,
                    'has_unread' => $accountCount > 0,
                ],
            ],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'تم تحديد الإشعار كمقروء',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
            'data' => [
                'success' => true,
            ],
        ]);
    }

    /**
     * Get single notification
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        // Auto-mark as read when viewing
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'data' => [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ],
        ]);
    }

    private function unreadMessagesCountForUser(string $userId): int
    {
        return Message::query()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->whereHas('conversation', function ($query) use ($userId) {
                $query->where(function ($nested) use ($userId) {
                    $nested->where(function ($q) use ($userId) {
                        $q->where('user_one_id', $userId)
                            ->whereNull('user_one_deleted_at');
                    })->orWhere(function ($q) use ($userId) {
                        $q->where('user_two_id', $userId)
                            ->whereNull('user_two_deleted_at');
                    });
                });
            })
            ->count();
    }

    /**
     * @param Collection<int, mixed> $notifications
     */
    private function countByTypes(Collection $notifications, array $types): int
    {
        return $notifications->whereIn('type', $types)->count();
    }

    /**
     * @param Collection<int, mixed> $notifications
     */
    private function countWalletNotifications(Collection $notifications): int
    {
        return $notifications->filter(function ($notification): bool {
            if (in_array($notification->type, self::WALLET_NOTIFICATION_TYPES, true)) {
                return true;
            }

            if (!in_array($notification->type, [
                CancellationRequestNotification::class,
                CourseCancelledNotification::class,
            ], true)) {
                return false;
            }

            $payload = is_array($notification->data) ? $notification->data : [];
            return (int) ($payload['refund_amount'] ?? 0) > 0;
        })->count();
    }
}

