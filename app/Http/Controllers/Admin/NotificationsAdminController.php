<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Notifications\AdminMessageNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class NotificationsAdminController extends BaseController
{
    public function index()
    {
        $users = User::select('id','name','phone_with_cc')->latest()->limit(50)->get();
        return view('admin.notifications.index', compact('users'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required','in:user,trainers'],
            'user_id' => ['required_if:audience,user','nullable','uuid'],
            'title' => ['required','string','max:120'],
            'message' => ['required','string','max:1000'],
        ]);

        $notification = new AdminMessageNotification($data['title'], $data['message']);

        if ($data['audience'] === 'user') {
            $user = User::findOrFail($data['user_id']);
            $user->notify($notification);
        } else {
            $trainers = User::role('TRAINER')->select('id')->cursor();
            foreach ($trainers->chunk(200) as $chunk) {
                Notification::send($chunk->all(), $notification);
            }
        }

        return back()->with('status', 'تم إرسال الإشعار');
    }
}
