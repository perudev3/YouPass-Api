<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventUserRole extends Model
{
    protected $fillable = ['user_id', 'event_id', 'role'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
