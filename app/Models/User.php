<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use App\Support\StorageUrl;
use App\Support\WalletAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_with_cc',
        'user_type',
        'whatsapp_enabled',
        'country_id',
        'profile_picture_id',
        'currency',
        'referral_code',
        'referred_by',
        'points_balance',
        'bank_account',
        'iban',
        'bank_name',
        'bank_country_id',
        'banned_until',
        'banned_reason',
        'version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'whatsapp_enabled' => 'bool',
            'version' => 'integer',
            'banned_until' => 'datetime',
        ];
    }

    protected function pointsBalance(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value): float => WalletAmount::minorToMajor((int) $value),
            set: static fn (mixed $value): int => WalletAmount::majorToMinor($value),
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = substr(bin2hex(random_bytes(6)), 0, 12);
            }
            if (empty($user->user_type)) {
                $user->user_type = UserType::USER;
            }
        });
        static::updating(function (self $user) {
            if ($user->isDirty()) {
                $user->version = (int) $user->version + 1;
            }
        });
    }

    public function trainerProfile()
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function isBanned(): bool
    {
        return ($this->deleted_at !== null) || ($this->banned_until && $this->banned_until->isFuture());
    }

    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function rates()
    {
        return $this->hasMany(Rating::class);
    }

    public function bankCountry()
    {
        return $this->belongsTo(Country::class, 'bank_country_id');
    }

    public function profilePicture()
    {
        return $this->belongsTo(Upload::class, 'profile_picture_id');
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (! $this->profile_picture_id) {
            return null;
        }

        if (! $this->relationLoaded('profilePicture')) {
            $this->load('profilePicture');
        }

        if (! $this->profilePicture) {
            return null;
        }

        return StorageUrl::make($this->profilePicture->path, $this->profilePicture->disk);
    }

    public function isCaptain(): bool
    {
        return $this->user_type === UserType::CAPTAIN;
    }

    public function isUser(): bool
    {
        return $this->user_type === UserType::USER;
    }

    public function plan()
    {
        return $this->hasMany(Plan::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function pointsBalanceMinor(): int
    {
        return (int) $this->getRawOriginal('points_balance');
    }

    public function setPointsBalanceMinor(int $amountMinor): self
    {
        $this->attributes['points_balance'] = max(0, $amountMinor);

        return $this;
    }

    public function routeNotificationForFcm(?Notification $notification = null): array
    {
        return $this->deviceTokens()
            ->pluck('token')
            ->filter(static fn (mixed $token): bool => is_string($token) && $token !== '')
            ->values()
            ->all();
    }
}
