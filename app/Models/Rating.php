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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }
}

