<?php

declare(strict_types=1);

namespace App\Models;

class CancellationRequest extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_request_id',
        'user_id',
        'reason',
        'status',
        'admin_notes',
        'refund_amount_minor',
        'processed_by',
        'processed_at',
        'version',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'refund_amount_minor' => 'integer',
        'version' => 'integer',
    ];

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}


