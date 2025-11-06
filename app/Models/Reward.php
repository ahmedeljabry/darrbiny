<?php

declare(strict_types=1);

namespace App\Models;

class Reward extends BaseModel
{
    protected $fillable = ['title','required_points','active','order','image'];

    protected $casts = [
        'required_points' => 'integer',
        'active' => 'bool',
        'order' => 'integer',
    ];

    public function redemptions()
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id');
    }
}

