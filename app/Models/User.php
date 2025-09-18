<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, HasUuids, SoftDeletes;

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
        'whatsapp_enabled',
        'country_id',
        'city_id',
        'currency',
        'referral_code',
        'referred_by',
        'points_balance',
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
            'whatsapp_enabled' => 'bool',
            'points_balance' => 'integer',
            'version' => 'integer',
            'banned_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = substr(bin2hex(random_bytes(6)), 0, 12);
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
}
