<?php

declare(strict_types=1);

namespace App\Models;

class TrainingDay extends BaseModel
{
    public const STATUS_SUBMITTED = 'submitted_by_trainer';
    public const STATUS_APPROVED = 'approved_by_user';
    public const STATUS_REJECTED = 'rejected_by_user';

    protected $fillable = ['user_request_id','trainer_id','date','hours_done','notes','status','rejection_reason','version'];

    protected $casts = [
        'date' => 'date',
        'hours_done' => 'integer',
        'version' => 'integer',
    ];
}

