<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Models\UserDeviceToken;

class DeviceTokenService
{
    public function store(User $user, ?string $token, ?string $platform = null, ?string $deviceName = null): ?UserDeviceToken
    {
        $token = is_string($token) ? trim($token) : null;

        if ($token === null || $token === '') {
            return null;
        }

        return UserDeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]
        );
    }

    public function delete(User $user, string $token): bool
    {
        return UserDeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->delete() > 0;
    }
}
