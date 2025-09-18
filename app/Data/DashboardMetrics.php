<?php

declare(strict_types=1);

namespace App\Data;

final class DashboardMetrics
{
    public function __construct(
        public readonly int $usersTotal,
        public readonly int $usersNew,
        public readonly int $plansTotal,
        public readonly int $plansNew,
        public readonly int $citiesTotal,
        public readonly int $citiesNew,
        public readonly int $countriesTotal,
        public readonly int $countriesNew,
    ) {}
}

