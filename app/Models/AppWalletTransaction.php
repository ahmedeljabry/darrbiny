<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class AppWalletTransaction extends BaseModel
{
    use SoftDeletes;

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    public const SOURCE_MANUAL_DEPOSIT = 'manual_deposit';
    public const SOURCE_TRAINER_DUES_WITHDRAWAL = 'trainer_dues_withdrawal';
    public const SOURCE_PACKAGE_REFUND_WITHDRAWAL = 'package_refund_withdrawal';
    public const SOURCE_PROFIT_WITHDRAWAL = 'profit_withdrawal';

    protected $fillable = [
        'direction',
        'source',
        'amount_minor',
        'notes',
        'created_by',
        'version',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'version' => 'integer',
    ];

    public static function sourceLabels(): array
    {
        return [
            self::SOURCE_MANUAL_DEPOSIT => 'إيداع في محفظة التطبيق',
            self::SOURCE_TRAINER_DUES_WITHDRAWAL => 'سحب مستحقات مدرب',
            self::SOURCE_PACKAGE_REFUND_WITHDRAWAL => 'سحب استرداد باقة ملغية',
            self::SOURCE_PROFIT_WITHDRAWAL => 'سحب أرباح',
        ];
    }

    public static function withdrawalSourceLabels(): array
    {
        return [
            self::SOURCE_TRAINER_DUES_WITHDRAWAL => self::sourceLabels()[self::SOURCE_TRAINER_DUES_WITHDRAWAL],
            self::SOURCE_PACKAGE_REFUND_WITHDRAWAL => self::sourceLabels()[self::SOURCE_PACKAGE_REFUND_WITHDRAWAL],
            self::SOURCE_PROFIT_WITHDRAWAL => self::sourceLabels()[self::SOURCE_PROFIT_WITHDRAWAL],
        ];
    }

    public static function sourceLabelFor(?string $source): string
    {
        return self::sourceLabels()[$source] ?? (string) $source;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
