<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum Period: string
{
    case TODAY = 'today';
    case WEEK = 'week';
    case MONTH = 'month';
    case ALL = 'all';

    public static function fromString(?string $value): self
    {
        return match (strtolower((string) $value)) {
            'today' => self::TODAY,
            'week' => self::WEEK,
            'month' => self::MONTH,
            default => self::ALL,
        };
    }

    public function range(): ?CarbonImmutable
    {
        $now = CarbonImmutable::now();
        return match ($this) {
            self::TODAY => $now->startOfDay(),
            self::WEEK => $now->startOfWeek(),
            self::MONTH => $now->startOfMonth(),
            self::ALL => null,
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::TODAY => 'اليوم',
            self::WEEK => 'هذا الأسبوع',
            self::MONTH => 'هذا الشهر',
            self::ALL => 'الكل',
        };
    }
}

