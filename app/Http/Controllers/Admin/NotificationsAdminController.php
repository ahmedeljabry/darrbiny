<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\AdminMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Notification;

class NotificationsAdminController extends BaseController
{
    public function index()
    {
        $users = User::query()
            ->select('id', 'name', 'phone_with_cc')
            ->orderBy('name')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.notifications.index', compact('users'));
    }

    public function users(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim((string) ($data['q'] ?? ''));
        $page = (int) ($data['page'] ?? 1);
        $perPage = 20;

        $query = User::query()
            ->select('id', 'name', 'phone_with_cc')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search) ?: $search;

            $query->where(function ($userQuery) use ($search, $digits): void {
                $userQuery->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone_with_cc', 'like', "%{$search}%")
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone_with_cc, '+', ''), ' ', ''), '-', '') like ?", ["%{$digits}%"])
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'text' => trim(($user->name ?: 'بدون اسم').' — '.($user->phone_with_cc ?: $user->id)),
            ])->values()->all(),
            'pagination' => [
                'more' => $users->hasMorePages(),
            ],
        ]);
    }

    public function view(Request $request)
    {
        $user = auth()->user();
        $type = $request->query('type');
        $read = $request->query('read');

        $query = $user->notifications();

        if ($type) {
            $query->where('type', 'like', "%{$type}%");
        }

        if ($read === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($read === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('admin.notifications.view', compact('notifications', 'type', 'read'));
    }

    public function show(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        $redirectRoute = $this->resolveNotificationRedirect($notification->data ?? []);
        if ($redirectRoute !== null) {
            return redirect()->to($redirectRoute)->with('notification', $notification);
        }

        return redirect()->back()->with('notification', $notification);
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'تم تحديد جميع الإشعارات كمقروءة');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:user,trainers,trainees'],
            'user_id' => ['required_if:audience,user', 'nullable', 'uuid'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $notification = new AdminMessageNotification($data['title'], $data['message']);

        if ($data['audience'] === 'user') {
            $user = User::findOrFail($data['user_id']);
            $recipientCount = 1;
            $deviceTokenCount = $user->deviceTokens()->count();
            $user->notify($notification);
        } else {
            $query = $this->resolveAudienceQuery($data['audience']);
            $recipientCount = (clone $query)->count();
            $deviceTokenCount = $this->countDeviceTokensForAudience($query);

            $query->with('deviceTokens:id,user_id,token')->chunkById(200, function ($chunk) use ($notification): void {
                Notification::send($chunk, $notification);
            });
        }

        $response = back()->with('status', $this->buildStatusMessage($recipientCount, $deviceTokenCount));

        return $response;
    }

    private function buildStatusMessage(int $recipientCount, int $deviceTokenCount): string
    {
        return "تم إرسال الإشعار إلى {$recipientCount} مستخدمين. الأجهزة المستهدفة: {$deviceTokenCount}";
    }

    private function resolveAudienceQuery(string $audience)
    {
        return $audience === 'trainers'
            ? User::role('TRAINER')
            : User::role('USER');
    }

    private function countDeviceTokensForAudience($query): int
    {
        return UserDeviceToken::query()
            ->whereIn('user_id', (clone $query)->select('users.id'))
            ->count();
    }

    private function resolveNotificationRedirect(array $data): ?string
    {
        $type = $data['type'] ?? null;

        return match ($type) {
            'trainer_profile_update', 'trainer_registration_pending_approval' => filled($data['trainer_id'] ?? null)
                ? route('admin.users.show', $data['trainer_id'])
                : null,
            'support_ticket_created', 'support_ticket_user_reply' => filled($data['ticket_id'] ?? null)
                ? route('admin.support.show', $data['ticket_id'])
                : null,
            'wallet_topup_request' => filled($data['transaction_id'] ?? null)
                ? route('admin.wallet-transactions.show', $data['transaction_id'])
                : null,
            'wallet_withdraw_request' => filled($data['transaction_id'] ?? null)
                ? route('admin.withdrawal-requests.show', $data['transaction_id'])
                : null,
            'prize_request' => filled($data['redemption_id'] ?? null)
                ? route('admin.prize-redemptions.show', $data['redemption_id'])
                : null,
            'cancellation_request' => filled($data['cancellation_request_id'] ?? null)
                ? route('admin.cancellation-requests.show', $data['cancellation_request_id'])
                : null,
            default => null,
        };
    }
}
