<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function issueTokens(User $user): array
    {
        $abilities = ['*'];
        $access = $user->createToken('access', $abilities, now()->addHours(2));

        $refreshPlain = Str::random(64);
        $refresh = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($refreshPlain),
            'access_token_id' => $access->accessToken->id,
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $access->plainTextToken,
            'access_token_expires_at' => $access->accessToken->expires_at,
            'refresh_token' => $refreshPlain,
            'refresh_token_expires_at' => $refresh->expires_at,
        ];
    }

    public function refresh(User $user, string $refreshToken): array
    {
        $record = RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()->first();

        if (!$record || !Hash::check($refreshToken, $record->token_hash)) {
            abort(401, 'Invalid refresh token');
        }

        $record->revoked_at = now();
        $record->save();

        // Re-issue
        return $this->issueTokens($user);
    }
}

