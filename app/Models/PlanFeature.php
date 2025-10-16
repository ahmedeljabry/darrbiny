<?php

declare(strict_types=1);

namespace App\Models;

class PlanFeature extends BaseModel
{
    protected $fillable = [
        'plan_id','label','position'
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

