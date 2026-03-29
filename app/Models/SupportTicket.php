<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class SupportTicket extends BaseModel
{
    protected $fillable = ['user_id','name','phone_with_cc','email','subject','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->latest();
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('ADMIN')) {
            return $query;
        }

        return $query->where(function (Builder $ticketQuery) use ($user): void {
            $ticketQuery->where('user_id', $user->id);

            if (filled($user->email)) {
                $ticketQuery->orWhere('email', $user->email);
            }

            if (filled($user->phone_with_cc)) {
                $ticketQuery->orWhere('phone_with_cc', $user->phone_with_cc);
            }
        });
    }
}
