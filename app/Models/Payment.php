<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends BaseModel
{
    public const TYPE_RESERVATION_FEE = 'reservation_fee';
    public const TYPE_PLAN_FULL = 'plan_full';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id','user_request_id','amount_minor','currency','type','provider','provider_ref','status','app_fee_minor','trainer_net_minor','version'
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'app_fee_minor' => 'integer',
        'trainer_net_minor' => 'integer',
        'version' => 'integer',
    ];
}

