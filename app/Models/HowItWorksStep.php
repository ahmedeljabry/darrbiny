<?php

declare(strict_types=1);

namespace App\Models;

class HowItWorksStep extends BaseModel
{
    protected $fillable = ['section_id','title'];

    public function section()
    {
        return $this->belongsTo(HowItWorksSection::class, 'section_id');
    }
}

