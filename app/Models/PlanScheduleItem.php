<?php

declare(strict_types=1);

namespace App\Models;

class PlanScheduleItem extends BaseModel
{
    protected $fillable = [
        'plan_id',
        'day_number',
        'title',
        'position',
        'version',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'position' => 'integer',
        'version' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserScheduleProgress::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('day_number');
    }
}

