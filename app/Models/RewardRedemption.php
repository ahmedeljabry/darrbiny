<?php

declare(strict_types=1);

namespace App\Models;

class RewardRedemption extends BaseModel
{
    protected $fillable = ['user_id','reward_id','points_spent','status'];

    protected $casts = [
        'points_spent' => 'integer',
    ];
}

