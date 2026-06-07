<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ReportCurrencyConverter;
use App\Support\WalletAmount;
use App\Support\WalletCurrency;

class WalletTransaction extends BaseModel
{
    const TYPE_TOPUP_REQUEST = 'topup_request';

    const TYPE_WITHDRAW_REQUEST = 'withdraw_request';

    const TYPE_REFUND = 'refund';

    const TYPE_PAYMENT = 'payment';

    const TYPE_ADJUSTMENT = 'adjustment';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'type',
        'status',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'notes',
        'version',
    ];

    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime',
        'version' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by')->withTrashed();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function amountMinor(): int
    {
        return (int) $this->getRawOriginal('amount');
    }

    public function amountMajor(): float
    {
        return WalletAmount::minorToMajor($this->amountMinor());
    }

    public function transactionCurrency(): string
    {
        $currency = strtoupper(trim((string) $this->currency));

        $user = $this->relationLoaded('user')
            ? $this->user
            : $this->user()->with(['country', 'bankCountry'])->first();

        $countryCurrency = WalletCurrency::countryCurrencyForUser($user);

        if ($countryCurrency !== null) {
            return $countryCurrency;
        }

        if ($currency !== '') {
            return $currency;
        }

        return WalletCurrency::forUser($user);
    }

    public function reportAmountMinor(?ReportCurrencyConverter $converter = null): int
    {
        $converter ??= app(ReportCurrencyConverter::class);

        return $converter->convertMinor($this->amountMinor(), $this->transactionCurrency());
    }
}
