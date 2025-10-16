<?php

declare(strict_types=1);

namespace App\Models;

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
}
