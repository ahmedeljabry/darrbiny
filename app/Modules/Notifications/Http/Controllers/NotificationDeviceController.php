<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Controllers;

use App\Models\UserDeviceToken;
use App\Modules\Notifications\Http\Requests\DeleteDeviceTokenRequest;
use App\Modules\Notifications\Http\Requests\StoreDeviceTokenRequest;
use Illuminate\Routing\Controller as BaseController;

class NotificationDeviceController extends BaseController
{
    public function store(StoreDeviceTokenRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $deviceToken = UserDeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'تم حفظ جهاز الإشعارات بنجاح',
            'data' => [
                'id' => $deviceToken->id,
                'token' => $deviceToken->token,
                'platform' => $deviceToken->platform,
                'device_name' => $deviceToken->device_name,
                'last_used_at' => $deviceToken->last_used_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(DeleteDeviceTokenRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $deleted = UserDeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json([
            'message' => 'تم حذف جهاز الإشعارات بنجاح',
            'data' => [
                'deleted' => $deleted > 0,
            ],
        ]);
    }
}
