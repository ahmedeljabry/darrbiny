<?php

declare(strict_types=1);

namespace App\Models;

class Referral extends BaseModel
{
    protected $fillable = ['owner_user_id','code','total_points_earned','total_redemptions'];
}

