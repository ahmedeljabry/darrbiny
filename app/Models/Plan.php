<?php

declare(strict_types=1);

namespace App\Models;

class Plan extends BaseModel
{
    protected $fillable = [
        'title',
        'description',
        'price_min',
        'badge_discount',
        'deposit_amount',
        'duration_days',
        'hours_count',
        'session_count',
        'level',
        'country_id',
        'city_id',
        'is_active',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'hours_count' => 'integer',
        'session_count' => 'integer',
        'is_active' => 'bool',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function userRequests()
    {
        return $this->hasMany(UserRequest::class);
    }

    public function features()
    {
        return $this->hasMany(PlanFeature::class)->orderBy('position');
    }

    public function scopeActive($q){ return $q->where('is_active', true); }
    public function scopeByLocation($q, ?string $countryId, ?string $cityId){
        return $q->when($countryId, fn($qq)=>$qq->where('country_id',$countryId))
                ->when($cityId, fn($qq)=>$qq->where('city_id',$cityId));
    }
}
