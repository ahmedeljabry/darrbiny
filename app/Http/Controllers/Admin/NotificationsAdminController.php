<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Modules\Notifications\Services\NotificationTopicService;
use App\Notifications\AdminMessageNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationsAdminController extends BaseController
{
    public function __construct(
        private readonly Messaging $messaging,
        private readonly NotificationTopicService $topics,
    ) {}

    public function index()
    {
        $users = User::select('id','name','phone_with_cc')->latest()->limit(50)->get();
        return view('admin.notifications.index', compact('users'));
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
        
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        // Redirect to trainer profile page for trainer approval related notifications
        if (
            isset($notification->data['type'])
            && in_array($notification->data['type'], ['trainer_profile_update', 'trainer_registration_pending_approval'], true)
        ) {
            $trainerId = $notification->data['trainer_id'] ?? null;
            if ($trainerId) {
                return redirect()->route('admin.users.show', $trainerId)->with('notification', $notification);
            }
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
            'audience' => ['required','in:user,trainers,trainees'],
            'user_id' => ['required_if:audience,user','nullable','uuid'],
            'title' => ['required','string','max:120'],
            'message' => ['required','string','max:1000'],
        ]);

        $notification = new AdminMessageNotification($data['title'], $data['message']);

        if ($data['audience'] === 'user') {
            $user = User::findOrFail($data['user_id']);
            $recipientCount = 1;
            $deviceTokenCount = null;
            $user->notify(new AdminMessageNotification(
                $data['title'],
                $data['message'],
                databaseOnly: true,
            ));

            $this->messaging->send($this->buildTopicMessage(
                $data['title'],
                $data['message'],
                $this->topics->userTopic($user),
            ));
        } else {
            $topic = $this->resolveAudienceTopic($data['audience']);
            $query = $this->resolveAudienceQuery($data['audience']);
            $recipientCount = (clone $query)->count();
            $deviceTokenCount = null;

            $query->select('id')->chunk(200, function ($chunk) use ($data): void {
                Notification::send($chunk, new AdminMessageNotification(
                    $data['title'],
                    $data['message'],
                    databaseOnly: true,
                ));
            });

            $this->messaging->send($this->buildTopicMessage($data['title'], $data['message'], $topic));
        }

        $response = back()->with('status', $this->buildStatusMessage($recipientCount, $deviceTokenCount));

        return $response;
    }

    private function buildStatusMessage(int $recipientCount, ?int $deviceTokenCount = null): string
    {
        if ($deviceTokenCount === null) {
            return "تم إرسال الإشعار إلى {$recipientCount} مستخدمين عبر Topic";
        }

        return "تم إرسال الإشعار إلى {$recipientCount} مستخدمين. الأجهزة المستهدفة: {$deviceTokenCount}";
    }

    private function resolveAudienceQuery(string $audience)
    {
        return $audience === 'trainers'
            ? User::role('TRAINER')
            : User::role('USER');
    }

    private function resolveAudienceTopic(string $audience): string
    {
        return $audience === 'trainers'
            ? (string) config('services.firebase.topics.trainers', 'trainers')
            : (string) config('services.firebase.topics.trainees', 'trainees');
    }

    private function buildTopicMessage(string $title, string $message, string $topic): CloudMessage
    {
        return CloudMessage::new()
            ->toTopic($topic)
            ->withNotification(FirebaseNotification::create($title, $message))
            ->withData([
                'title' => $title,
                'message' => $message,
                'notification_type' => 'admin_message',
                'topic' => $topic,
            ])
            ->withHighestPossiblePriority()
            ->withDefaultSounds();
    }
}
