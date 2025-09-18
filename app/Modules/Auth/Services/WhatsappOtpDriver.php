<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Log;

class WhatsappOtpDriver implements OtpChannel
{
    public function send(string $phoneWithCc, string $message): void
    {
        // Stub driver: integrate Twilio/Meta/360dialog here
        $masked = substr($phoneWithCc, 0, 3).'****'.substr($phoneWithCc, -2);
        Log::channel('stack')->info('otp.send', [
            'phone' => $masked,
            'provider' => 'stub-whatsapp',
        ]);
    }
}

