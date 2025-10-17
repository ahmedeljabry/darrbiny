<?php

declare(strict_types=1);

namespace App\Models;

class Favorite extends BaseModel
{
    protected $fillable = ['user_id','trainer_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}

