<?php

declare(strict_types=1);

namespace App\Models;

class Rating extends BaseModel
{
    protected $fillable = ['user_id','trainer_id','user_request_id','stars','comment','version'];

    protected $casts = [
        'stars' => 'integer',
        'version' => 'integer',
    ];
}

