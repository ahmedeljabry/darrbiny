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
        'price_max',
        'badge_discount',
        'deposit_amount',
        'duration_days',
        'hours_count',
        'session_count',
        'level',
        'country_id',
        'is_active',
        'show_on_home',
        'position',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'hours_count' => 'integer',
        'session_count' => 'integer',
        'is_active' => 'bool',
        'show_on_home' => 'bool',
        'position' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
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
    public function scopeOrdered($q){ return $q->orderBy('position')->orderBy('created_at')->orderBy('title'); }
    public function scopeByCountry($q, ?string $countryId)
    {
        return $q->when($countryId, fn ($qq) => $qq->where('country_id', $countryId));
    }
}
