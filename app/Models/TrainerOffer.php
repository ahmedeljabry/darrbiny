<?php

declare(strict_types=1);

namespace App\Models;

class TrainerOffer extends BaseModel
{
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = ['user_request_id','trainer_id','price_minor','message','status','version'];

    protected $casts = [
        'price_minor' => 'integer',
        'version' => 'integer',
    ];

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }
}

