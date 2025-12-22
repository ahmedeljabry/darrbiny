<?php

declare(strict_types=1);

namespace App\Models;

class TrainerProfile extends BaseModel
{
    protected $fillable = [
        'user_id',
        'bio',
        'country_id',
        'city_id',
        'car_available',
        'pickup_available',
        'car_type',
        'car_model_year',
        'has_driving_license',
        'rating_count',
        'rating_avg',
        'verified_at',
        'pending_approval',
        'pending_changes',
        'pending_approval_at',
        'version',
    ];

    protected $casts = [
        'car_available' => 'bool',
        'pickup_available' => 'bool',
        'has_driving_license' => 'bool',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'verified_at' => 'datetime',
        'pending_approval' => 'bool',
        'pending_changes' => 'array',
        'pending_approval_at' => 'datetime',
        'version' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
