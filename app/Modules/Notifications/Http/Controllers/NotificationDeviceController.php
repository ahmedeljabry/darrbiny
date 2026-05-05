<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Notifications\Http\Requests\DeleteDeviceTokenRequest;
use App\Modules\Notifications\Http\Requests\StoreDeviceTokenRequest;
use App\Modules\Notifications\Services\DeviceTokenService;
use Illuminate\Routing\Controller as BaseController;

class NotificationDeviceController extends BaseController
{
    public function __construct(
        private readonly DeviceTokenService $deviceTokens,
    ) {}

    public function store(StoreDeviceTokenRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $deviceToken = $this->deviceTokens->store(
            $user,
            $validated['token'],
            $validated['platform'] ?? null,
            $validated['device_name'] ?? null,
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
        $validated = $request->validated();

        $deleted = $this->deviceTokens->delete($request->user(), $validated['token']);

        return response()->json([
            'message' => 'تم حذف جهاز الإشعارات بنجاح',
            'data' => [
                'deleted' => $deleted,
            ],
        ]);
    }
}
