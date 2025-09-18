<?php

declare(strict_types=1);

namespace App\Models;

class TrainerProfile extends BaseModel
{
    protected $fillable = [
        'user_id','bio','country_id','city_id','car_available','pickup_available','rating_count','rating_avg','verified_at','version'
    ];

    protected $casts = [
        'car_available' => 'bool',
        'pickup_available' => 'bool',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'verified_at' => 'datetime',
        'version' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

