<?php

declare(strict_types=1);

namespace App\Models;

class Reward extends BaseModel
{
    protected $fillable = ['title','required_points','stock','active'];

    protected $casts = [
        'required_points' => 'integer',
        'stock' => 'integer',
        'active' => 'bool',
    ];
}

