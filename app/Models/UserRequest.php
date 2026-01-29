<?php

declare(strict_types=1);

namespace App\Models;

class UserRequest extends BaseModel
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_AWAITING_OFFERS = 'awaiting_offers';
    public const STATUS_OFFER_SELECTED = 'offer_selected';
    public const STATUS_PAID = 'paid';
    public const STATUS_IN_TRAINING = 'in_training';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id','trainer_id','plan_id','start_date','description','has_user_car','wants_trainer_car','needs_pickup','latitude','longitude','status','currency','app_fee_reserved_minor','total_paid_minor','version'
    ];

    protected $casts = [
        'start_date' => 'date',
        'has_user_car' => 'bool',
        'wants_trainer_car' => 'bool',
        'needs_pickup' => 'bool',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'app_fee_reserved_minor' => 'integer',
        'total_paid_minor' => 'integer',
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

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function offers()
    {
        return $this->hasMany(TrainerOffer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function trainingDays()
    {
        return $this->hasMany(TrainingDay::class);
    }

    public function cancellationRequest()
    {
        return $this->hasOne(CancellationRequest::class);
    }

    public function scheduleProgress()
    {
        return $this->hasMany(UserScheduleProgress::class);
    }
}
