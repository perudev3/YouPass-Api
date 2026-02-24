<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'ticket_id',
        'event_id',
        'code',
        'status',
        'claimed_by',
        'used_at',
        'guest_phone',
        'token'
    ];

      public function event()
    {
        return $this->belongsTo(\App\Event::class);
    }

    public function ticket()
    {
        return $this->belongsTo(\App\Ticket::class);
    }

    public function claimedBy()
    {
        return $this->belongsTo(\App\User::class, 'claimed_by');
    }
}
