<?php

declare(strict_types=1);

namespace App\Models;

class HowItWorksSection extends BaseModel
{
    protected $fillable = ['title','position'];

    public function steps()
    {
        return $this->hasMany(HowItWorksStep::class, 'section_id')->orderBy('position');
    }
}

