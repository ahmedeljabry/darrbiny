<?php

declare(strict_types=1);

namespace App\Models;

class UserScheduleProgress extends BaseModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_request_id',
        'plan_schedule_item_id',
        'day_number',
        'is_checked',
        'checked_at',
        'status',
        'sent_at',
        'rejection_reason',
        'rating',
        'rating_titles',
        'rating_comment',
        'version',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
        'sent_at' => 'datetime',
        'rating' => 'integer',
        'rating_titles' => 'array',
        'version' => 'integer',
    ];

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }

    public function planScheduleItem()
    {
        return $this->belongsTo(PlanScheduleItem::class);
    }
}

