<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class GatewayWalletTransaction extends BaseModel
{
    use SoftDeletes;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const SOURCE_BANK_DEPOSIT = 'bank_deposit';

    public const SOURCE_APP_WALLET_TRANSFER = 'app_wallet_transfer';

    public const SOURCE_GATEWAY_FEE = 'gateway_fee';

    public const SOURCE_OTHER = 'other';

    protected $fillable = [
        'gateway',
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

    public static function directionLabels(): array
    {
        return [
            self::DIRECTION_IN => 'وارد',
            self::DIRECTION_OUT => 'صادر',
        ];
    }

    public static function incomingSourceLabels(): array
    {
        return [
            self::SOURCE_BANK_DEPOSIT => 'تحويل من البنك',
            self::SOURCE_OTHER => 'أخرى',
        ];
    }

    public static function outgoingSourceLabels(): array
    {
        return [
            self::SOURCE_APP_WALLET_TRANSFER => 'حساب محفظة التطبيق',
            self::SOURCE_GATEWAY_FEE => 'رسوم بوابة الدفع',
            self::SOURCE_OTHER => 'أخرى',
        ];
    }

    public static function sourceLabels(): array
    {
        return self::incomingSourceLabels() + self::outgoingSourceLabels();
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
