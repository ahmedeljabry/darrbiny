<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\StorageUrl;

class Plan extends BaseModel
{
    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'title',
        'description',
        'image',
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
        'show_on_home',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'hours_count' => 'integer',
        'session_count' => 'integer',
        'is_active' => 'bool',
        'show_on_home' => 'bool',
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

    public function scheduleItems()
    {
        return $this->hasMany(PlanScheduleItem::class)->ordered();
    }

    public function getImageUrlAttribute(): ?string
    {
        return StorageUrl::make($this->image);
    }

    public function scopeActive($q){ return $q->where('is_active', true); }
    public function scopeHome($q){ return $q->where('show_on_home' , true); }
    public function scopeByLocation($q, ?string $cityId){
        return $q->when($cityId, fn($qq) => $qq->where('city_id', $cityId));
    }
}
