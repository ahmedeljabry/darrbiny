<?php

declare(strict_types=1);

namespace App\Models;

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
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
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
}

