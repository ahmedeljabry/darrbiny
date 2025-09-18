<?php

declare(strict_types=1);

namespace App\Models;

class Payout extends BaseModel
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = ['trainer_id','user_request_id','amount_minor','currency','status','bank_ref','processed_at','version'];

    protected $casts = [
        'amount_minor' => 'integer',
        'processed_at' => 'datetime',
        'version' => 'integer',
    ];
}

