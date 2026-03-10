<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Collection;

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
        'user_id',
        'trainer_id',
        'plan_id',
        'country_id',
        'area_level_1',
        'area_level_2',
        'area_level_3',
        'locality',
        'start_date',
        'start_time',
        'description',
        'has_user_car',
        'wants_trainer_car',
        'needs_pickup',
        'latitude',
        'longitude',
        'status',
        'currency',
        'app_fee_reserved_minor',
        'total_paid_minor',
        'retry_source_request_id',
        'version',
    ];

    protected $casts = [
        'start_date' => 'date',
        'has_user_car' => 'bool',
        'wants_trainer_car' => 'bool',
        'needs_pickup' => 'bool',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'country_id' => 'string',
        'app_fee_reserved_minor' => 'integer',
        'total_paid_minor' => 'integer',
        'retry_source_request_id' => 'string',
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

    public function country()
    {
        return $this->belongsTo(Country::class);
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

    public function retrySource()
    {
        return $this->belongsTo(self::class, 'retry_source_request_id');
    }

    public function retryChild()
    {
        return $this->hasOne(self::class, 'retry_source_request_id');
    }

    public function paymentsCollection(): Collection
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments;
        }

        return $this->payments()->get();
    }

    public function successfulPayments(): Collection
    {
        return $this->paymentsCollection()
            ->filter(fn (Payment $payment) => $payment->status === Payment::STATUS_SUCCEEDED)
            ->sortByDesc(fn (Payment $payment) => $payment->created_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function totalSuccessfulPaymentsMinor(): int
    {
        return (int) $this->successfulPayments()->sum('amount_minor');
    }

    public function latestSuccessfulFullPayment(): ?Payment
    {
        return $this->successfulPayments()->first(
            fn (Payment $payment) => $payment->isFullType()
        );
    }

    public function latestSuccessfulPartialPayment(): ?Payment
    {
        return $this->successfulPayments()->first(
            fn (Payment $payment) => $payment->isPartialType()
        );
    }
}
