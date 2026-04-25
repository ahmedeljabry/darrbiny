<?php

declare(strict_types=1);

namespace App\Models;

class AppExpense extends BaseModel
{
    public const TYPE_OPERATING_EXPENSE = 'operating_expense';
    public const TYPE_TRAINER_DUES = 'trainer_dues';
    public const TYPE_PACKAGE_REFUND = 'package_refund';
    public const TYPE_PROFIT_WITHDRAWAL = 'profit_withdrawal';

    protected $fillable = [
        'type',
        'amount_minor',
        'notes',
        'created_by',
        'updated_by',
        'version',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'version' => 'integer',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_OPERATING_EXPENSE => 'مصروفات تشغيل',
            self::TYPE_TRAINER_DUES => 'مستحقات مدربين',
            self::TYPE_PACKAGE_REFUND => 'استرداد باقات',
            self::TYPE_PROFIT_WITHDRAWAL => 'سحب أرباح',
        ];
    }

    public static function formTypeLabels(): array
    {
        return [
            self::TYPE_OPERATING_EXPENSE => self::typeLabels()[self::TYPE_OPERATING_EXPENSE],
        ];
    }

    public static function typeLabelFor(?string $type): string
    {
        return self::typeLabels()[$type] ?? (string) $type;
    }

    public function typeLabel(): string
    {
        return self::typeLabelFor($this->type);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
