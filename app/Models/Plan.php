<?php

declare(strict_types=1);

namespace App\Models;

class Plan extends BaseModel
{
    protected $fillable = [
        'title','description','hours_count','training_type','country_id','city_id','base_price_minor','is_active','version'
    ];

    protected $casts = [
        'hours_count' => 'integer',
        'base_price_minor' => 'integer',
        'is_active' => 'bool',
        'version' => 'integer',
    ];
}

