<?php

declare(strict_types=1);

namespace App\Models;

class SupportTicketMessage extends BaseModel
{
    protected $fillable = ['ticket_id','user_id','author_type','message'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

