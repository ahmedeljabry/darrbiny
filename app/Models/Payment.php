<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends BaseModel
{
    public const TYPE_RESERVATION_FEE = 'reservation_fee';
    public const TYPE_PLAN_FULL = 'plan_full';
    public const TYPE_PLAN_PARTIAL = 'plan_partial';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'user_request_id',
        'amount_minor',
        'currency',
        'type',
        'payment_method',
        'status',
        'app_fee_minor',
        'trainer_net_minor',
        'version'
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'app_fee_minor' => 'integer',
        'trainer_net_minor' => 'integer',
        'version' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function userRequest()
    {
        return $this->belongsTo(UserRequest::class);
    }

    public static function partialTypes(): array
    {
        return [
            self::TYPE_PLAN_PARTIAL,
            self::TYPE_RESERVATION_FEE,
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_RESERVATION_FEE => 'رسوم الحجز',
            self::TYPE_PLAN_PARTIAL => 'رسوم الحجز',
            self::TYPE_PLAN_FULL => 'دفع كلي',
        ];
    }

    public static function reportTypeLabels(): array
    {
        return [
            self::TYPE_RESERVATION_FEE => 'رسوم الحجز الثابتة',
            self::TYPE_PLAN_PARTIAL => 'رسوم الحجز',
            self::TYPE_PLAN_FULL => 'دفع كلي',
        ];
    }

    public static function typeLabelFor(?string $type): string
    {
        return self::typeLabels()[$type] ?? (string) $type;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_SUCCEEDED => 'نجحت',
            self::STATUS_FAILED => 'فشلت',
        ];
    }

    public static function statusLabelFor(?string $status): string
    {
        return self::statusLabels()[$status] ?? (string) $status;
    }

    public function typeLabel(): string
    {
        return self::typeLabelFor($this->type);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function isFullType(): bool
    {
        return $this->type === self::TYPE_PLAN_FULL;
    }

    public function isPartialType(): bool
    {
        return in_array($this->type, self::partialTypes(), true);
    }
}
