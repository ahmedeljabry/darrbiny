<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

interface OtpChannel
{
    public function send(string $phoneWithCc, string $message): void;
}

