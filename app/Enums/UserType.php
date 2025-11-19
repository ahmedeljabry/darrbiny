<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case USER = 'user';
    case CAPTAIN = 'captain';

    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::CAPTAIN => 'Captain',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::USER => 'مستخدم',
            self::CAPTAIN => 'كابتن',
        };
    }
}

