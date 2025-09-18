<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public function __construct(public readonly int $amountMinor, public readonly string $currency)
    {
    }

    public static function fromMajor(float $amount, string $currency): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function toMajor(): float
    {
        return $this->amountMinor / 100.0;
    }

    public function format(): string
    {
        return number_format($this->toMajor(), 2).' '.$this->currency;
    }
}

