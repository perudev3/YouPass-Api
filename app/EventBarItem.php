<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventBarItem extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'category',
        'price',
        'image',
        'available'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
