<?php

declare(strict_types=1);

namespace App\Models;

class RewardRedemption extends BaseModel
{
    protected $fillable = ['user_id','reward_id','points_spent','status','rejection_reason'];

    protected $casts = [
        'points_spent' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }
}
