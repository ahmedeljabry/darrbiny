<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class OtpService
{
    public function __construct(private readonly OtpChannel $channel) {}

    public function request(string $phoneWithCc): void
    {
        $ipKey = 'otp:ip:'.request()->ip();
        $phoneKey = 'otp:phone:'.$phoneWithCc;

        if ((int) Cache::get($ipKey.':lock', 0) === 1 || (int) Cache::get($phoneKey.':lock', 0) === 1) {
            throw new RuntimeException('Too many attempts', 429);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($phoneKey, json_encode(['otp' => $otp, 'attempts' => 0]), now()->addMinutes(5));
        Cache::put($ipKey, now()->timestamp, now()->addMinutes(1));

        $this->channel->send($phoneWithCc, "Your verification code is {$otp}");
    }

    public function verify(string $phoneWithCc, string $otp): bool
    {
        $phoneKey = 'otp:phone:'.$phoneWithCc;
        $payload = Cache::get($phoneKey);
        if (!$payload) {
            return false;
        }
        $data = json_decode((string) $payload, true) ?: [];
        $attempts = (int) ($data['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::put($phoneKey.':lock', 1, now()->addMinutes(15));
            return false;
        }
        if (($data['otp'] ?? null) !== $otp) {
            $data['attempts'] = $attempts + 1;
            Cache::put($phoneKey, json_encode($data), now()->addMinutes(5));
            return false;
        }
        Cache::forget($phoneKey);
        return true;
    }
}

